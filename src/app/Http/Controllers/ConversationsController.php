<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contacts;
use App\Models\Conversations;
use App\Models\Participants;
use App\Models\Messages;
use App\Models\ZoomMeeting;
use DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ConversationsController extends Controller
{
  public function conversationFetch(Request $request)
  {

    $validator = Validator::make($request->all(), [
         'page' => 'nullable|integer',
     ]);


     if ($validator->fails()) {
          return response()->json(['status' => 0 , 'message' => $validator->messages()->first()], 200);
     }


    $pageNumber = $request->input('page');

    $perPage = 10;
    $conversations = Conversations::paginate($perPage, ['*'], 'page', $pageNumber);

    $conversations->getCollection()->transform(function ($value) {

        $lastMessage = Messages::where('conversation_id','=',$value->id)->orderBy('id','ASC')->first();
        if ($lastMessage)
        {
          $sender = Contacts::where('id','=',$lastMessage->sender_id)->first();
          $value->senderName = $sender->name;
          $value->lastMessage = $lastMessage->content;
        }
        else {
          $value->senderName = null;
          $value->lastMessage = null;
        }
        return $value;
    });

    $conversations->makeHidden(['created_at','updated_at','id']);
    return response()->json(['status' => 1 , 'data' => $conversations], 200);
  }



  public function conversationCreate(Request $request)
  {

    $validator = Validator::make($request->all(), [
         'title' => 'required|max:255',
         'participants' => 'required|array',
         'participants.*' => 'integer|exists:contacts,id'
     ]);


     if ($validator->fails()) {
          return response()->json(['status' => 0 , 'message' => $validator->messages()->first()], 200);
     }

     $title = $request->input('title');
     $participants = $request->input('participants');

     try {
          DB::beginTransaction();

          $conversation = new conversations;

          $conversation->title = $title;

          $conversation->save();

          foreach ($participants as $participant_input_id) {

            $participant = new participants;
            $participant->conversation_id = $conversation->id;
            $participant->participant_id = $participant_input_id;
            $participant->is_active = 1;

            $participant->save();
          }

          DB::commit();
          return response()->json(['status' => 1 , 'data' => ['conversation_id' => $conversation->id]], 200);

      } catch (\PDOException $e) {

          DB::rollBack();

          return response()->json(['status' => 0 , 'message' => 'Unable to perform action . Please try again Later'], 200);
      }


  }


  public function addMessage(Request $request, $conversation_id)
  {

    $conversation = Conversations::where('id',$conversation_id)->exists();

    if (!$conversation)
    {
      return response()->json(['status' => 0 , 'message' => 'Conversation is invalid.'], 404);
    }

    $validator = Validator::make($request->all(), [
         'content' => 'required|max:255',
         'type' => 'required|in:Meeting,Text',
         'senderId' => 'required|integer|exists:participants,participant_id,conversation_id,'.$conversation_id
     ]);


     if ($validator->fails()) {
          return response()->json(['status' => 0 , 'message' => $validator->messages()->first()], 200);
     }

     $content = $request->input('content');
     $type = $request->input('type');
     $senderId = $request->input('senderId');


     $message = new Messages;
     $message->conversation_id = $conversation_id;
     $message->sender_id = $senderId;
     $message->content = $content;
     $message->type = $type;
     $message->save();

     if ($type == 'Text')
     {

       return response()->json(['status' => 1 , 'data' => ['message_id' => $message->id]], 200);

     }
     else {

       if (substr( $content, 0, 8 ) == 'Schedule')
       {

         $dateSchedule = $this->getScheduleTime($content,"Schedule");

         if (!$dateSchedule) {
           return response()->json(['status' => 0 , 'message' => 'Error Processing Message.'], 200);
         }

         $zoomSchedule =  new ZoomMeeting;
         $zoomSchedule->conversation_id = $conversation_id;
         $zoomSchedule->schedule_timestamp = $dateSchedule;
         $zoomSchedule->zoom_meeting_id = null;
         $zoomSchedule->operation_type = 'Schedule';
         $zoomSchedule->status = 0;

         $zoomSchedule->save();

         return response()->json(['status' => 1 , 'message' => 'SUCCESS'], 200);

       }
       elseif (substr( $content, 0, 10 ) == 'Reschedule') {

         $dateSchedule = $this->getScheduleTime($content,"Reschedule");

         if (!$dateSchedule) {
           return response()->json(['status' => 0 , 'message' => 'Error Processing Message.'], 200);
         }

         $oldSchedule = ZoomMeeting::where('conversation_id','=',$conversation_id)->where('schedule_timestamp','=',$dateSchedule['old'])->where('status','=',1)->first();

         if (!$oldSchedule)
         {
           return response()->json(['status' => 0 , 'message' => 'No Zoom meeting found'], 200);
         }

         $zoomSchedule =  new ZoomMeeting;
         $zoomSchedule->conversation_id = $conversation_id;
         $zoomSchedule->schedule_timestamp = $dateSchedule['new'];
         $zoomSchedule->zoom_meeting_id = $oldSchedule->zoom_meeting_id;
         $zoomSchedule->operation_type = 'Reschedule';
         $zoomSchedule->status = 0;

         $zoomSchedule->save();

         return response()->json(['status' => 1 , 'message' => 'SUCCESS'], 200);


       }
       elseif (substr( $content, 0, 6 ) == 'Cancel') {

         $dateSchedule = $this->getScheduleTime($content,"Cancel");

         if (!$dateSchedule) {
           return response()->json(['status' => 0 , 'message' => 'Error Processing Message.'], 200);
         }

         $oldSchedule = ZoomMeeting::where('conversation_id','=',$conversation_id)->where('schedule_timestamp','=',$dateSchedule)->where('status','=',1)->first();

         if (!$oldSchedule)
         {
           return response()->json(['status' => 0 , 'message' => 'No Zoom meeting found'], 200);
         }

         $zoomSchedule =  new ZoomMeeting;
         $zoomSchedule->conversation_id = $conversation_id;
         $zoomSchedule->schedule_timestamp = $dateSchedule;
         $zoomSchedule->zoom_meeting_id = $oldSchedule->zoom_meeting_id;
         $zoomSchedule->operation_type = 'Cancel';
         $zoomSchedule->status = 0;

         $zoomSchedule->save();

         return response()->json(['status' => 1 , 'message' => 'SUCCESS'], 200);


       }
       else {
         return response()->json(['status' => 0 , 'message' => 'Error Processing Message.'], 200);
       }



     }


  }


  private function getScheduleTime($msgString, $type)
  {

    if ($type == 'Schedule') {
      try {
        $ScheduleTime = explode("for ",$msgString)[1];
        $scheduleDateTimeStamp = Carbon::createFromFormat('H:m d/m/Y', $ScheduleTime)->timestamp;
        return $scheduleDateTimeStamp;

      } catch (\Exception $e) {
        // return response()->json(['status' => 0 , 'message' => 'Error Processing Message.'], 200);
        return false;
      }
    }
    elseif ($type == 'Reschedule') {
      try {
        $ScheduleTime = explode(" to ",explode("Reschedule ",$msgString)[1]);
        $scheduleDateTimeStamp['old'] = Carbon::createFromFormat('H:m d/m/Y', $ScheduleTime[0])->timestamp;
        $scheduleDateTimeStamp['new'] = Carbon::createFromFormat('H:m d/m/Y', $ScheduleTime[1])->timestamp;
        return $scheduleDateTimeStamp;

      } catch (\Exception $e) {
        return false;
      }
    }
    elseif ($type == 'Cancel') {
      try {
        $ScheduleTime = explode("Cancel ",$msgString)[1];
        $scheduleDateTimeStamp = Carbon::createFromFormat('H:m d/m/Y', $ScheduleTime)->timestamp;
        return $scheduleDateTimeStamp;

      } catch (\Exception $e) {
        // return response()->json(['status' => 0 , 'message' => 'Error Processing Message.'], 200);
        return false;
      }
    }


  }


  public function conversationFetchID(Request $request, $conversation_id)
  {

      $conversation = Conversations::where('id',$conversation_id)->first();

      if (!$conversation)
      {
        return response()->json(['status' => 0 , 'message' => 'Conversation is invalid.'], 404);
      }

      $lastMessage = Messages::where('conversation_id','=',$conversation_id)->orderBy('id','desc')->first();
      $sender = Contacts::where('id','=',$lastMessage->sender_id)->first();

      $participants = Participants::where('conversation_id','=',$conversation_id)->where('is_active','=',1)->get();

      $participantsData = [];
      foreach ($participants as $key => $participant)
      {
        $tmpData = [];
        $tmpData['id'] = $participant->id;
        $tmpData['name'] = $participant->contact->name;
        $participantsData[] = $tmpData;
      }

      $messagesData = [];
      $messages = Messages::where('conversation_id','=',$conversation_id)->orderBy('id','desc')->take(10)->get();
      foreach ($messages as $message)
      {
        $tmpData = [];
        $tmpData['id'] = $message->id;
        $tmpData['createdAt'] = $message->created_at;
        $tmpData['content'] = $message->content;
        $tmpData['senderId'] = $message->sender_id;
        $tmpData['type'] = $message->type;
        $tmpData['senderName'] = $message->sender->name;
        $messagesData[] = $tmpData;

      }

      $response = [
        'title' => $conversation->title,
        'senderName' => $sender->name,
        'senderId' => $sender->id,
        'participants' => $participantsData,
        'messages' => $messagesData
      ];


      return response()->json(['status' => 1 , 'data' => $response], 200);
  }




  public function fetchMessage(Request $request, $conversation_id, $message_id)
  {

    $message = Messages::where('id',$message_id)->where('conversation_id','=',$conversation_id)->first();

    if (!$message)
    {
      return response()->json(['status' => 0 , 'message' => 'Conversation or message is invalid.'], 404);
    }

    $response = [
      'content' => $message->content,
      'senderName' => $message->sender->name,
      'senderId' => $message->sender_id,
      'type'  => $message->type,
      'createdAt' => $message->created_at
    ];

    return response()->json(['status' => 1 , 'data' => $response], 200);

  }


}
