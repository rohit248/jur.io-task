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



  


}
