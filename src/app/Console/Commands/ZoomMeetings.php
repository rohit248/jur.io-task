<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ZoomMeeting;
use App\Traits\ZoomMeetingTrait;
use Illuminate\Http\Request;
use Log;

class ZoomMeetings extends Command
{

    use ZoomMeetingTrait;

    const MEETING_TYPE_INSTANT = 1;
    const MEETING_TYPE_SCHEDULE = 2;
    const MEETING_TYPE_RECURRING = 3;
    const MEETING_TYPE_FIXED_RECURRING_FIXED = 8;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ZoomMeetings:Handle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command will be used to connect with zoom apis.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pendingMeetingActions = ZoomMeeting::where('status','=',0)->first();

        if (!$pendingMeetingActions)
        {
          Log::debug('No pending actions found...');
          return 0;
        }

        $data = ['start_time' => $pendingMeetingActions->schedule_timestamp,
                          'topic' => 'Meeting',
                          'agenda' => 'dasda'
                        ];

        Log::debug('pending action found...',[$pendingMeetingActions]);

        if ($pendingMeetingActions->operation_type == 'Schedule')
        {

          $path = 'users/me/meetings';
          $response = $this->zoomPost($path, [
              'topic' => $data['topic'],
              'type' => self::MEETING_TYPE_SCHEDULE,
              'start_time' => $this->toZoomTimeFormat($data['start_time']),
              'duration' => 30,
              'agenda' => $data['agenda'],
              'settings' => [
                  'host_video' => false,
                  'participant_video' => false,
                  'waiting_room' => true,
              ]
          ]);

          if (!$response->status() === 201)
          {

            Log::debug('Something went wrong');
          }
          $meetingID = json_decode($response->body(), true)['id'];
          $pendingMeetingActions->response_data = $response->body();
          $pendingMeetingActions->status = 1;
          $pendingMeetingActions->zoom_meeting_id = $meetingID;
          $pendingMeetingActions->save();

        }
        elseif ($pendingMeetingActions->operation_type == 'Reschedule') {

          $path = 'meetings/' . $pendingMeetingActions->zoom_meeting_id;
          $response = $this->zoomPatch($path, [
              'topic' => $data['topic'],
              'type' => self::MEETING_TYPE_SCHEDULE,
              'start_time' => (new \DateTime($data['start_time']))->format('Y-m-d\TH:i:s'),
              'duration' => 30,
              'agenda' => $data['agenda'],
              'settings' => [
                  'host_video' => false,
                  'participant_video' => false,
                  'waiting_room' => true,
              ]
          ]);

          if (!$response->status() === 204)
          {

            Log::debug('Something went wrong');
          }

          $pendingMeetingActions->status = 1;
          $pendingMeetingActions->save();

        }
        elseif ($pendingMeetingActions->operation_type == 'Cancel')
        {

          $path = 'meetings/' . $pendingMeetingActions->zoom_meeting_id;
          $response = $this->zoomDelete($path);

          if (!$response->status() === 204)
          {

            Log::debug('Something went wrong');
          }

          $pendingMeetingActions->status = 1;
          $pendingMeetingActions->save();

        }

    }
}
