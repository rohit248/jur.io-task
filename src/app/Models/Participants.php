<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participants extends Model
{
    use HasFactory;

    /**
    * The table associated with the model.
    *
    * @var string
    */
   protected $table = 'participants';

   protected $primaryKey = 'id';

    public function contact()
    {
        return $this->hasOne(Contacts::class, 'id', 'participant_id');
    }
}
