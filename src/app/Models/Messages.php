<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    use HasFactory;


    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'messages';

   protected $primaryKey = 'id';


   public function sender()
   {
       return $this->hasOne(Contacts::class, 'id', 'sender_id');
   }
}
