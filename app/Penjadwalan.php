<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Penjadwalan extends Model
{
    //
     protected $fillable = ['id_block','id_mata_kuliah','id_ruangan','tanggal','waktu_mulai','waktu_selesai','status_jadwal'];

    	public function block()
		  {
		  	return $this->hasOne('App\Master_block','id','id_block');
		  }

    	public function mata_kuliah()
		  {
		  	return $this->hasOne('App\Master_mata_kuliah','id','id_mata_kuliah');
		  }

    	public function ruangan()
		  {
		  	return $this->hasOne('App\Master_ruangan','id','id_ruangan');
		  } 
}