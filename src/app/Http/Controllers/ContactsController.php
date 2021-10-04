<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contacts;
use Illuminate\Support\Facades\Validator;


class ContactsController extends Controller
{

    public function contactsFetch(Request $request)
    {

      $validator = Validator::make($request->all(), [
           'page' => 'nullable|integer',
       ]);


       if ($validator->fails()) {
            return response()->json(['status' => 0 , 'message' => $validator->messages()->first()], 200);
       }


      $pageNumber = $request->input('page');

      $perPage = 10;
      $contacts = Contacts::paginate($perPage, ['*'], 'page', $pageNumber);
      $contacts->makeHidden(['created_at','updated_at']);
      return response()->json(['status' => 1 , 'data' => $contacts], 200);
    }
}
