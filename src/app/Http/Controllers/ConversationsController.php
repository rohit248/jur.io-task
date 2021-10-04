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


  


}
