<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Master_ruangan;
use App\Penjadwalan;
use App\User;
use App\Jadwal_dosen;
use App\Presensi;
use App\PresensiMahasiswa;
use App\Master_block;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class AndroidController extends Controller
{
    //

        public function authenticate(Request $request)
    {

        if (Auth::attempt(['email' => $request->username, 'password' => $request->password])) {
            // Authentication passed...
            $response["value"] = 1;
          $response["message"] = "Login Berhasil";
          return  json_encode($response);
        }
        else {
          $response["value"] = 2;
          $response["message"] = "Login Gagal";
          return  json_encode($response);
        }
    }

    public function login_dosen_android(Request $request){


        if (Auth::attempt(['email' => $request->username, 'password' => $request->password]) && Auth::user()->status) {
            // Authentication passed...

            $user_otoritas = Auth::user()->roles->first()->name;
            // cek otoritas

                  if ($user_otoritas == 'dosen') {
                    // jika otoritas nya dosen maka login akan berhasil

                                $response["value"] = 1;// value = 1
                                $response["message"] = "Login Berhasil"; // login berhasil
                                return  json_encode($response);// data yang dikembalikan berupa json

                  }else{

                                $response["value"] = 2;// value = 2
                                $response["message"] = "Login Gagal, anda bukan dosen!!";// login gagal, kerena user bukan dosen
                                return  json_encode($response);// data yang dikembalikan berupa json
                  }


            }

            else {

                $response["value"] = 3;// value = 3
                $response["message"] = "Anda Tidak Bisa Login Di Karenakan Belum Di Konfirmasi Oleh Admin";// login gagal
                return  json_encode($response);// data yang dikembalikan berupa json

            }


    }

//PROSES TAMBAH RUANGAN
    public function tambah_ruangan (Request $request){

        $this->validate($request, [
            'kode_ruangan'   => 'required|unique:master_ruangans,kode_ruangan,'
            ]);

         $master_ruangans = Master_ruangan::create([ 
            'kode_ruangan' =>$request->kode_ruangan,
            'nama_ruangan'=>$request->nama_ruangan,
            'lokasi_ruangan'=>$request->gedung,
            'longitude'=>$request->longitude,
            'latitude'=>$request->latitude,
            'batas_jarak_absen'=>$request->batas_jarak]);

          $response["value"] = 1;
          $response["message"] = "Ruangan Berhasil Ditambah";
          return  json_encode($response);
    }

//PROSES MEAMPILKAN RUANGAN
    public function list_ruangan(Request $request){        
      $ruangan =  Master_ruangan::all();
      $result = array();

        foreach ($ruangan as $row ) {       
          array_push($result, array('id'=>$row['id'], 'kode_ruangan'=>$row['kode_ruangan'], 'nama_ruangan'=>$row['nama_ruangan'], 'lokasi_ruangan' => $row['lokasi_ruangan'], 'latitude' => $row['latitude'], 'longitude' => $row['longitude'], 'batas_jarak_absen' => $row['batas_jarak_absen']));
        }

      echo json_encode(array("value"=>1,"result"=>$result));

    }

//PROSES UPDATE RUANGAN

    public function update_ruangan(Request $request) {

        Master_ruangan::where('id', $request->id) ->update([ 
            'kode_ruangan' =>$request->kode_ruangan,
            'nama_ruangan'=>$request->nama_ruangan,
            'lokasi_ruangan'=>$request->gedung,
            'longitude'=>$request->longitude,
            'latitude'=>$request->latitude,
            'batas_jarak_absen'=>$request->batas_jarak]);

        $response["value"] = 1;
        $response["message"] = "Ruangan Berhasil Diubah";
        
        return  json_encode($response);
    }


//PROSES HAPUS RUANGAN

    public function hapus_ruangan(Request $request)   {

        Master_ruangan::destroy($request->id);
        
        $response["value"] = 1;
        $response["message"] = "Ruangan Berhasil Dihapus";
        
        return  json_encode($response);
    }


// function tanggal terbalik
    function tanggal_terbalik($tanggal){
    
    $date= date_create($tanggal);
    $date_terbalik =  date_format($date,"d/m/Y");
    return $date_terbalik;
    }


  function tanggal_mysql($tanggal2){

   $date= date_create($tanggal2);
   $date_format = date_format($date,"Y-m-d");
   return $date_format;
  }

// list jadwal dosen
    public function list_jadwal_dosen(Request $request){

        $dosen = $request->username;// DOSEN YANG LOGIN
        $id_dosen = User::select('id')->where('email',$dosen)->first();//  AMBIL ID DOSEN
        $value = 0;
        $result = array();// ARRAY RESULT
        $waktu = date("Y-m-d H:i:s");

        $penjadwalans = Jadwal_dosen::select('jadwal_dosens.id_jadwal AS id_jadwal','jadwal_dosens.id_ruangan AS id_ruangan','jadwal_dosens.tanggal AS tanggal', 'jadwal_dosens.waktu_mulai AS waktu_mulai', 'jadwal_dosens.waktu_selesai AS waktu_selesai','master_mata_kuliahs.nama_mata_kuliah','master_ruangans.nama_ruangan AS ruangan','master_ruangans.longitude AS longitude','master_ruangans.latitude AS latitude','master_ruangans.batas_jarak_absen AS batas_jarak_absen','penjadwalans.tipe_jadwal AS tipe_jadwal')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH,  RUANGAN, LATITUDE , LONGITUDE, BATAS JARAK ABSEN , TIPE JADWAL

                        ->leftJoin('master_mata_kuliahs','jadwal_dosens.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','jadwal_dosens.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                        ->leftJoin('penjadwalans','jadwal_dosens.id_jadwal','=','penjadwalans.id')
                        // lEFT JOIN PENJADWALN
                        ->where('jadwal_dosens.id_dosen',$id_dosen->id)
                        //WHERE ID DOSEN = ID DOSEN LOGIN
                        ->where(DB::raw('CONCAT(jadwal_dosens.tanggal, " ", jadwal_dosens.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('jadwal_dosens.status_jadwal',0)
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA
                        ->orderBy(DB::raw('CONCAT(jadwal_dosens.tanggal, " ", jadwal_dosens.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->groupBy('jadwal_dosens.id_jadwal')// GROUP BY ID JADWAL
                        ->get();


      foreach ($penjadwalans as $list_jadwal_dosen) {// FOREACH
        $value = $value + 1;

          // jika tipe jadwal nya kosong atau null
            if ($list_jadwal_dosen['tipe_jadwal'] == "" OR $list_jadwal_dosen['tipe_jadwal'] == NULL) {
              // maka tipe jadwal = -
              $tipe_jadwal = "-";              

            }else{ // jika tidak
              $tipe_jadwal = $list_jadwal_dosen['tipe_jadwal'];
            }

        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_dosen['tanggal']),
                          // TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_dosen['waktu_mulai'] ." - " . $list_jadwal_dosen['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $list_jadwal_dosen['nama_mata_kuliah'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_dosen['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_dosen['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_dosen['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_dosen['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_dosen['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_dosen['batas_jarak_absen'], // BATAS JARAK ABSEN
                        'tipe_jadwal' => $tipe_jadwal // TIPE JADWAL


                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

     // DATA YANG DIKIRIM BERUPA JSON
      return json_encode(array('value' => $value , 'result'=>$result));

    }// end function list jadwal dosen

// search jadwal dosen
    public function search_jadwal_dosen(Request $request){

        $search = $request->search;// REQUEST SEARCH
        $dosen = $request->username;// DOSEN YANG LOGIN
        $id_dosen = User::select('id')->where('email',$dosen)->first();//  AMBIL ID DOSEN
        $waktu = date("Y-m-d H:i:s");

        $result = array();// ARRAY RESULT

        $penjadwalans = Jadwal_dosen::select('jadwal_dosens.id_jadwal AS id_jadwal','jadwal_dosens.id_ruangan AS id_ruangan','jadwal_dosens.tanggal AS tanggal', 'jadwal_dosens.waktu_mulai AS waktu_mulai', 'jadwal_dosens.waktu_selesai AS waktu_selesai','master_mata_kuliahs.nama_mata_kuliah','master_ruangans.nama_ruangan AS ruangan','master_ruangans.longitude AS longitude','master_ruangans.latitude AS latitude','master_ruangans.batas_jarak_absen AS batas_jarak_absen','penjadwalans.tipe_jadwal AS tipe_jadwal')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH, RUANGAN, LATITUDE , LONGITUDE, TIPE JADWAL

                        ->leftJoin('master_mata_kuliahs','jadwal_dosens.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','jadwal_dosens.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                         ->leftJoin('penjadwalans','jadwal_dosens.id_jadwal','=','penjadwalans.id')
                        // lEFT JOIN PENJADWALN
                        ->where('jadwal_dosens.id_dosen',$id_dosen->id)
                        //WHERE ID DOSEN = ID DOSEN LOGIN
                        ->where(DB::raw('CONCAT(jadwal_dosens.tanggal, " ", jadwal_dosens.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('jadwal_dosens.status_jadwal',0)                        
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA  
                        ->where(function($query) use ($search){// search
                            $query->orWhere('jadwal_dosens.tanggal','LIKE',$search.'%')// OR LIKE TANGGAL
                                  ->orWhere(DB::raw('DATE_FORMAT(jadwal_dosens.tanggal, "%d/%m/%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d/m/y
                                  ->orWhere(DB::raw('DATE_FORMAT(jadwal_dosens.tanggal, "%d-%m-%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d-m-y
                                  ->orWhere('jadwal_dosens.waktu_mulai','LIKE',$search.'%')// OR LIKE WAKTU MULAI
                                  ->orWhere('master_mata_kuliahs.nama_mata_kuliah','LIKE',$search.'%')// OR LIKE NAMA MATA KULIAH
                                  ->orWhere('master_ruangans.nama_ruangan','LIKE',$search.'%');  //OR LIKE NAMA RUANGAN
                        })    // search  
                        ->orderBy(DB::raw('CONCAT(jadwal_dosens.tanggal, " ", jadwal_dosens.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->groupBy('jadwal_dosens.id_jadwal')// GROUP BY ID JADWAL
                        ->get();


      foreach ($penjadwalans as $list_jadwal_dosen) {// FOREACH

       // jika tipe jadwal nya kosong atau null
            if ($list_jadwal_dosen['tipe_jadwal'] == "" OR $list_jadwal_dosen['tipe_jadwal'] == NULL) {
              // maka tipe jadwal = -
              $tipe_jadwal = "-";              

            }else{ // jika tidak
              $tipe_jadwal = $list_jadwal_dosen['tipe_jadwal'];
            }
        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_dosen['tanggal']),
                          // TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_dosen['waktu_mulai'] ." - " . $list_jadwal_dosen['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $list_jadwal_dosen['nama_mata_kuliah'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_dosen['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_dosen['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_dosen['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_dosen['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_dosen['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_dosen['batas_jarak_absen'] , // BATAS JARAK ABSEN
                        'tipe_jadwal' => $tipe_jadwal // TIPE JADWAL 
                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

      // DATA YANG DIKEMBALIKAN  BERUPA JSON
      return json_encode(array('value' => '1' , 'result'=>$result));


    }// end search jadawal dosen

// function batal jadwal dosen 
    public function batal_jadwal_dosen(Request $request)
    {
            $id_jadwal = $request->id_jadwal;// id jadwal

            $penjadwalan = Penjadwalan::where("id",$id_jadwal)->update(["status_jadwal" => 2]);
             // update Penjadwalan (status jadwal di set = 2 atau "Batal") where id_jadwal dosen = $id jadwal dosen

            $jadwal_dosen = Jadwal_dosen::where("id_jadwal",$id_jadwal)->update(["status_jadwal" => 2]);
            // update jadwal dosen (status jadwal di set = 2 atau "Batal") where id_jadwal dosen = $id jadwal dosen

            // jika query berhasil di eksekusi
            if ($penjadwalan ==  true AND $jadwal_dosen == true) {
              
            // DATA YANG DIKEMBALIKAN  BERUPA JSON
            return json_encode(array('value' => '1' , 'message'=>'Jadwal Berhasil Di Batalkan'));

            }else{

             // DATA YANG DIKEMBALIKAN  BERUPA JSON
            return json_encode(array('value' => '0' , 'message'=>'Jadwal Gagal Di Batalkan'));

            }



    }

// presendi dosen
    public function presensi_dosen(Request $request){

      $dosen = $request->username;// DOSEN YANG LOGIN
      $id_dosen = User::select('id')->where('email',$dosen)->first();//  AMBIL ID DOSEN
      $id_jadwal = $request->id_jadwal;// ID JADWAL
      $id_ruangan = $request->id_ruangan; // ID RUANGAN
      $longitude = $request->longitude_sekarang;// LONGITUDE
      $latitude = $request->latitude_sekarang;// LATITUDE
      $image = $request->image; // FOTO ABSEN
      $jarak_ke_lokasi_absen = $request->jarak_ke_lokasi_absen; // JARK KE LOKASI ABSEN
      $waktu = date("Y-m-d H:i:s");// WAKTU SEKARANG
      $tanggal1 = $request->tanggal; // TANGGAL JADWAL
      $tanggal = Carbon::createFromFormat('d/m/Y', $tanggal1)->format('Y-m-d');// UBAH FORMAT TANGGAL D/M/Y MENJADI Y-M-D
      $waktu_jadwal = $request->waktu_jadwal;// WAKTU JADWAL (08:00 - 09:00)

      // EXPLODE WAKTU JADWAL
      $waktu_jadwal_dosen = explode(" - ", $waktu_jadwal); 
      $waktu_mulai = $waktu_jadwal_dosen[0]; // EXPLODE 0 ADALAH WAKTU MULAI
      $waktu_selesai = $waktu_jadwal_dosen[1]; // EXPLODE 1 ADALAH WAKTU SELESAI


      $waktu_jadwal_mulai = $tanggal ." ". $waktu_mulai;    // TANGGAL JADAWL DIJADIKAN SATU STRING DENGAN WAKTU MULAI  
      $waktu_jadwal_selesai = $tanggal ." ". $waktu_selesai;  // TANGGAL JADAWL DIJADIKAN SATU STRING DENGAN WAKTU SELESAI

      // JIKA WAKTU JADWAL SUDAH MULAI
      if ($waktu >= $waktu_jadwal_mulai AND $waktu <= $waktu_jadwal_selesai) {// JIKA WAKTU SAAT INI BERADA DIANTARA WAKTU MULAI DAN WAKTU SELESAI

      // CEK APAKAH DOSEN INI SUDAH ABSEN BELUM UNTUK JADWAL INI
      $query_cek_presensi = Presensi::where('id_jadwal',$id_jadwal) // WHERE ID JADWAL
                          ->where('id_user',$id_dosen->id)// AND ID DOSEN
                          ->count();

                              // JIKA 0, ARTINYA BELUM ABSEN
                    if ($query_cek_presensi == 0) {

                        // INSERT KE TABLE PRESENSI
                          $presensi = Presensi::create([
                          'id_user' => $id_dosen->id,// ID USER DOSEN
                          'id_jadwal' => $id_jadwal,// ID JADWAL
                          'id_ruangan' => $id_ruangan,// ID JADWAL
                          'longitude' => $longitude,// LONGITUDE
                          'latitude' => $latitude,// LATITUDE
                          'jarak_ke_lokasi_absen' => $jarak_ke_lokasi_absen // JARAK KE LOKASI ABSEN
                          ]);

                          // MEMBUAT NAMA FILE DENGAN EXTENSI PNG 
                          $filename = 'image' . DIRECTORY_SEPARATOR . str_random(40) . '.png';

                          // UPLOAD FOTO
                          file_put_contents($filename,base64_decode($image));
                           
                          // INSERT FOTO KE TABLE PRSENSI   
                          $presensi->foto = $filename;     
                          $presensi->save();  

                          // CEK ADA BERAPA DOSEN UNTUK JADWAL INI 
                          $count_jadwal_dosen = Jadwal_dosen::where('id_jadwal',$id_jadwal)->count();
                          
                          // CEK ADA BERAPA DOSEN YANG SUDAH HADIR UNTUK JADWAL INI       
                          $count_presensi = Presensi::where('id_jadwal',$id_jadwal)->count(); 

                                // JIKA SAMA
                                if ($count_jadwal_dosen == $count_presensi) {                  

                                  // MAKA JADWAL AKAN DIUPDATE STATUSNYA MENJADI TERLAKSANA

                                  $penjadwalan_terlaksana = Penjadwalan::where("id",$id_jadwal)->update(["status_jadwal" => 1]);
                                   // update Penjadwalan (status jadwal di set = 1 atau "TERLAKSANA") where id_jadwal dosen = $id jadwal dosen

                                  $jadwal_dosen_terlaksana = Jadwal_dosen::where("id_jadwal",$id_jadwal)->update(["status_jadwal" => 1]);
                                  // update jadwal dosen (status jadwal di set = 1 atau "TERLAKSANA") where id_jadwal dosen = $id jadwal dosen

                                }

                          $response["value"] = 1;// RESPONSE VALUE 1
                          $response["message"] = "Berhasil Absen";// RESPONSE BERHASIL ABSEN
                          // DATA DIKEMBALIKAN DALAM BENTUK JSON
                          return  json_encode($response);

                      
                    }else{// JIKA TIDAK NOL, MAKA DOSEN SUDAH ABSEN


                          $response["value"] = 2;// RESPONSE VALUE 0
                          $response["message"] = "Gagal Absen";// RESPONSE Gagal ABSEN
                          // DATA DIKEMBALIKAN DALAM BENTUK JSON
                          return  json_encode($response);

                    }// END          

      }else{

                          $response["value"] = 3;// RESPONSE VALUE 0
                          $response["message"] = "Gagal Absen, Jadwal belum dimulai";// RESPONSE Gagal ABSEN
                          // DATA DIKEMBALIKAN DALAM BENTUK JSON
                          return  json_encode($response);
      }

  

    
    }// PRESENSI

//MAHASISWA
    public function ubah_password_dosen (Request $request){

      $dosen = $request->username;// DOSEN YANG LOGIN
      $id_dosen = User::select('id')->where('email',$dosen)->first();//  AMBIL ID DOSEN

      $password_lama = $request->password_lama;
      $username_baru = $request->username_baru;
      $password_baru = $request->password_baru;


          if (Auth::attempt(['email' => $dosen, 'password' => $password_lama])) {
             


                  $update_user = User::where("id",$id_dosen->id)->update(["email" => $username_baru, "password" => bcrypt($password_baru)]);

            
                  $response["value"] = 1;// RESPONSE VALUE 0
                  $response["message"] = "Password Berhasil Di Ubah";// RESPONSE Gagal ABSEN
                                // DATA DIKEMBALIKAN DALAM BENTUK JSON
                  return  json_encode($response);

               }else{

                    $response["value"] = 0;// RESPONSE VALUE 0
                  $response["message"] = "Mohon Maaf Password Lama Anda Salah";// RESPONSE Gagal ABSEN
                                // DATA DIKEMBALIKAN DALAM BENTUK JSON

                  return json_encode($response);

              }     
  
    }

    //LOGIN ABSEN MAHASISWA
    public function login_mahasiswa_android(Request $request){

      if (Auth::attempt(['email' => $request->username, 'password' => $request->password])) { // Authentication passed...

        $user_otoritas = Auth::user()->roles->first()->name; // cek otoritas

          if ($user_otoritas == 'mahasiswa') { // jika otoritas nya mahasiswa maka login akan berhasil
            $response["value"] = 1;// value = 1
            $response["message"] = "Login Berhasil"; // login berhasil
          }
          else{
            $response["value"] = 2;// value = 2
            $response["message"] = "Login Gagal, Anda Bukan Mahasiswa!!";// login gagal, kerena user bukan mahasiswa
          }

      }
      else {
            $response["value"] = 3;// value = 3
            $response["message"] = "Login Gagal";// login gagal            
      }

      return  json_encode($response);// data yang dikembalikan berupa json

    }
    //LOGIN ABSEN MAHASISWA

    //DAFTAR JADWAL MAHASISWA
    public function list_jadwal_mahasiswa(Request $request){

        $mahasiswa = $request->username;// MAHASISWA YANG LOGIN
        $data_mahasiswa = User::select(['id', 'id_angkatan'])->where('email',$mahasiswa)->first();//  AMBIL ID MAHASISWA
        $data_block = Master_block::select('id')->where('id_angkatan',$data_mahasiswa->id_angkatan)->get();
        $value = 0;
        $result = array();// ARRAY RESULT
        $waktu = date("Y-m-d H:i:s");
        $hari_ini = date("Y-m-d");

        $array_block = array();
        foreach ($data_block as $data_blocks) {
          array_push($array_block, $data_blocks->id);
        }

        $penjadwalans = Penjadwalan::select('penjadwalans.id AS id_jadwal', 'penjadwalans.id_block AS id_block', 'penjadwalans.id_ruangan AS id_ruangan', 'penjadwalans.tipe_jadwal AS tipe_jadwal', 'penjadwalans.tanggal AS tanggal',  'penjadwalans.waktu_mulai AS waktu_mulai',  'penjadwalans.waktu_selesai AS waktu_selesai', 'master_mata_kuliahs.nama_mata_kuliah', 'master_ruangans.nama_ruangan AS ruangan', 'master_ruangans.longitude AS longitude', 'master_ruangans.latitude AS latitude', 'master_ruangans.batas_jarak_absen AS batas_jarak_absen')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH, DAN RUANGAN

                        ->leftJoin('master_mata_kuliahs','penjadwalans.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','penjadwalans.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                        ->whereIn('penjadwalans.id_block', $array_block)
                        //WHERE ID BLOK = ID BLOK USER LOGIN
                        ->where('penjadwalans.tanggal', '=', $hari_ini)
                        // JADWAL YANG TAMPIL ADALAH JADWAL HARI INI
                        ->where(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('penjadwalans.status_jadwal',0)
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA
                        ->orderBy(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->get();

      
      foreach ($penjadwalans as $list_jadwal_mahasiswa) {// FOREACH
        if ($list_jadwal_mahasiswa['nama_mata_kuliah'] == "") {
          $mata_kuliah = "-";
        }
        else{
          $mata_kuliah = $list_jadwal_mahasiswa['nama_mata_kuliah'];
        }

        $value = $value + 1;
        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_mahasiswa['tanggal']),// TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_mahasiswa['waktu_mulai'] ." - " . $list_jadwal_mahasiswa['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $mata_kuliah,// MATA KULIAH
                        'tipe_jadwal' => $list_jadwal_mahasiswa['tipe_jadwal'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_mahasiswa['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_mahasiswa['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_mahasiswa['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_mahasiswa['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_mahasiswa['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_mahasiswa['batas_jarak_absen'] // LONGITUDE


                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

     // DATA YANG DIKIRIM BERUPA JSON
      return json_encode(array('value' => $value , 'result'=>$result));

    }
    //DAFTAR JADWAL MAHASISWA

    //PRESENSI MAHASISWA
    // presendi dosen
    public function presensi_mahasiswa(Request $request){

      $mahasiswa = $request->username;// MAHASISWA YANG LOGIN
      $id_mahasiswa = User::select('id')->where('email',$mahasiswa)->first();//  AMBIL ID MAHASISWA
      $id_jadwal = $request->id_jadwal;// ID JADWAL
      $id_ruangan = $request->id_ruangan; // ID RUANGAN
      $longitude = $request->longitude_sekarang;// LONGITUDE
      $latitude = $request->latitude_sekarang;// LATITUDE
      $image = $request->image; // FOTO ABSEN
      $jarak_ke_lokasi_absen = $request->jarak_ke_lokasi_absen;
      $waktu = date("Y-m-d H:i:s");
      $tanggal_db = $request->tanggal;
      $tanggal = Carbon::createFromFormat('d/m/Y', $tanggal_db)->format('Y-m-d');
      $waktu_jadwal = $request->waktu_jadwal;

      $waktu_jadwal_mahasiswa = explode(" - ", $waktu_jadwal); 
      $waktu_mulai = $waktu_jadwal_mahasiswa[0]; 
      $waktu_selesai = $waktu_jadwal_mahasiswa[1];

      $waktu_jadwal_mulai = $tanggal ." ". $waktu_mulai;      
      $waktu_jadwal_selesai = $tanggal ." ". $waktu_selesai; 


      if ($waktu >= $waktu_jadwal_mulai AND $waktu <= $waktu_jadwal_selesai) {

      // CEK APAKAH MAHASISWA INI SUDAH ABSEN BELUM UNTUK JADWAL INI
      $query_cek_presensi = PresensiMahasiswa::where('id_jadwal',$id_jadwal) // WHERE ID JADWAL
                          ->where('id_user',$id_mahasiswa->id)// AND ID MAHASISWA
                          ->count();

                  // JIKA 0, ARTINYA BELUM ABSEN
                    if ($query_cek_presensi == 0) {

                        // INSERT KE TABLE PRESENSI
                          $presensi = PresensiMahasiswa::create([
                          'id_user' => $id_mahasiswa->id,// ID USER MAHASISWA
                          'id_jadwal' => $id_jadwal,// ID JADWAL
                          'id_ruangan' => $id_ruangan,// ID JADWAL
                          'longitude' => $longitude,// LONGITUDE
                          'latitude' => $latitude,// LATITUDE
                          'jarak_ke_lokasi_absen' => $jarak_ke_lokasi_absen 
                          ]);

                          // MEMBUAT NAMA FILE DENGAN EXTENSI PNG 
                          $filename = 'image' . DIRECTORY_SEPARATOR . str_random(40) . '.png';

                          // UPLOAD FOTO
                          file_put_contents($filename,base64_decode($image));
                           
                          // INSERT FOTO KE TABLE PRSENSI   
                          $presensi->foto = $filename;     
                          $presensi->save();

                          $response["value"] = 1;// RESPONSE VALUE 1
                          $response["message"] = "Berhasil Absen";// RESPONSE BERHASIL ABSEN     
                    }
                    else{// JIKA TIDAK NOL, MAKA MAHASISWA SUDAH ABSEN

                          $response["value"] = 2;// RESPONSE VALUE 0
                          $response["message"] = "Gagal Absen";// RESPONSE Gagal ABSEN
                          // DATA DIKEMBALIKAN DALAM BENTUK JSON
                          return  json_encode($response);

                    }// END
      }
      else{
                          $response["value"] = 3;// RESPONSE VALUE 0
                          $response["message"] = "Gagal Absen, Jadwal Belum Dimulai";// RESPONSE Gagal ABSEN
      }

       return  json_encode($response);
      //PRESENSI MAHASISWA
  }


// CARI JADWAL MAHSISWA
    public function search_jadwal_mahasiswa(Request $request){

        $search = $request->search;// REQUEST SEARCH
        $mahasiswa = $request->username;// MAHASISWA YANG LOGIN
        $data_mahasiswa = User::select(['id', 'id_angkatan'])->where('email',$mahasiswa)->first();//  AMBIL ID MAHASISWA
        $data_block = Master_block::select('id')->where('id_angkatan',$data_mahasiswa->id_angkatan)->get();
        $value = 0;
        $result = array();// ARRAY RESULT
        $waktu = date("Y-m-d H:i:s");
        $hari_ini = date("Y-m-d");

        $array_block = array();
        foreach ($data_block as $data_blocks) {
          array_push($array_block, $data_blocks->id);
        }

        $penjadwalans = Penjadwalan::select('penjadwalans.id AS id_jadwal', 'penjadwalans.id_block AS id_block', 'penjadwalans.id_ruangan AS id_ruangan', 'penjadwalans.tipe_jadwal AS tipe_jadwal', 'penjadwalans.tanggal AS tanggal',  'penjadwalans.waktu_mulai AS waktu_mulai',  'penjadwalans.waktu_selesai AS waktu_selesai', 'master_mata_kuliahs.nama_mata_kuliah', 'master_ruangans.nama_ruangan AS ruangan', 'master_ruangans.longitude AS longitude', 'master_ruangans.latitude AS latitude', 'master_ruangans.batas_jarak_absen AS batas_jarak_absen')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH, DAN RUANGAN

                        ->leftJoin('master_mata_kuliahs','penjadwalans.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','penjadwalans.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                        ->whereIn('penjadwalans.id_block', $array_block)
                        //WHERE ID BLOK = ID BLOK USER LOGIN
                        ->where('penjadwalans.tanggal', '=', $hari_ini)
                        // JADWAL YANG TAMPIL ADALAH JADWAL HARI INI
                        ->where(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('penjadwalans.status_jadwal',0)
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA
                        ->where(function($query) use ($search){// search
                            $query->orWhere('penjadwalans.tanggal','LIKE',$search.'%')// OR LIKE TANGGAL
                                  ->orWhere(DB::raw('DATE_FORMAT(penjadwalans.tanggal, "%d/%m/%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d/m/y
                                  ->orWhere(DB::raw('DATE_FORMAT(penjadwalans.tanggal, "%d-%m-%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d-m-y
                                  ->orWhere('penjadwalans.waktu_mulai','LIKE',$search.'%')// OR LIKE WAKTU MULAI
                                  ->orWhere('penjadwalans.tipe_jadwal','LIKE',$search.'%')// OR LIKE WAKTU MULAI
                                  ->orWhere('master_mata_kuliahs.nama_mata_kuliah','LIKE',$search.'%')// OR LIKE NAMA MATA KULIAH
                                  ->orWhere('master_ruangans.nama_ruangan','LIKE',$search.'%');  //OR LIKE NAMA RUANGAN
                        }) 
                        ->orderBy(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->get();

      foreach ($penjadwalans as $list_jadwal_mahasiswa) {// FOREACH
        if ($list_jadwal_mahasiswa['nama_mata_kuliah'] == "") {
          $mata_kuliah = "-";
        }
        else{
          $mata_kuliah = $list_jadwal_mahasiswa['nama_mata_kuliah'];
        }

        $value = $value + 1;
        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_mahasiswa['tanggal']),// TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_mahasiswa['waktu_mulai'] ." - " . $list_jadwal_mahasiswa['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $mata_kuliah,// MATA KULIAH
                        'tipe_jadwal' => $list_jadwal_mahasiswa['tipe_jadwal'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_mahasiswa['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_mahasiswa['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_mahasiswa['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_mahasiswa['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_mahasiswa['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_mahasiswa['batas_jarak_absen'] // LONGITUDE


                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

      // DATA YANG DIKEMBALIKAN  BERUPA JSON
      return json_encode(array('value' => '1' , 'result'=>$result));


    }
// END CARI JADWAL MAHSISWA

    //DAFTAR JADWAL MAHASISWA BESOK
    public function jadwal_besok(Request $request){

        $mahasiswa = $request->username;// MAHASISWA YANG LOGIN
        $data_mahasiswa = User::select(['id', 'id_angkatan'])->where('email',$mahasiswa)->first();//  AMBIL ID MAHASISWA
        $data_block = Master_block::select('id')->where('id_angkatan',$data_mahasiswa->id_angkatan)->get();
        $value = 0;
        $result = array();// ARRAY RESULT
        $waktu = date("Y-m-d H:i:s");
        $hari_besok = mktime (0,0,0, date("m"), date("d")+1,date("Y"));
        $tanggal_besok = date('Y-m-d',$hari_besok );// TANGGAL BESOK

        $array_block = array();
        foreach ($data_block as $data_blocks) {
          array_push($array_block, $data_blocks->id);
        }

        $penjadwalans = Penjadwalan::select('penjadwalans.id AS id_jadwal', 'penjadwalans.id_block AS id_block', 'penjadwalans.id_ruangan AS id_ruangan', 'penjadwalans.tipe_jadwal AS tipe_jadwal', 'penjadwalans.tanggal AS tanggal',  'penjadwalans.waktu_mulai AS waktu_mulai',  'penjadwalans.waktu_selesai AS waktu_selesai', 'master_mata_kuliahs.nama_mata_kuliah', 'master_ruangans.nama_ruangan AS ruangan', 'master_ruangans.longitude AS longitude', 'master_ruangans.latitude AS latitude', 'master_ruangans.batas_jarak_absen AS batas_jarak_absen')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH, DAN RUANGAN

                        ->leftJoin('master_mata_kuliahs','penjadwalans.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','penjadwalans.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                        ->whereIn('penjadwalans.id_block', $array_block)
                        //WHERE ID BLOK = ID BLOK USER LOGIN
                        ->where('penjadwalans.tanggal', '=', $tanggal_besok)
                        // JADWAL YANG TAMPIL ADALAH JADWAL HARI INI
                        ->where(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('penjadwalans.status_jadwal',0)
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA
                        ->orderBy(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->get();

      
      foreach ($penjadwalans as $list_jadwal_mahasiswa) {// FOREACH
        if ($list_jadwal_mahasiswa['nama_mata_kuliah'] == "") {
          $mata_kuliah = "-";
        }
        else{
          $mata_kuliah = $list_jadwal_mahasiswa['nama_mata_kuliah'];
        }

        $value = $value + 1;
        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_mahasiswa['tanggal']),// TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_mahasiswa['waktu_mulai'] ." - " . $list_jadwal_mahasiswa['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $mata_kuliah,// MATA KULIAH
                        'tipe_jadwal' => $list_jadwal_mahasiswa['tipe_jadwal'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_mahasiswa['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_mahasiswa['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_mahasiswa['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_mahasiswa['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_mahasiswa['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_mahasiswa['batas_jarak_absen'] // LONGITUDE


                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

     // DATA YANG DIKIRIM BERUPA JSON
      return json_encode(array('value' => $value , 'result'=>$result));

    }
    //DAFTAR JADWAL MAHASISWA BESOK


// CARI JADWAL MAHSISWA BESOK
    public function search_jadwal_mahasiswa_besok(Request $request){

        $search = $request->search;// REQUEST SEARCH
        $mahasiswa = $request->username;// MAHASISWA YANG LOGIN
        $data_mahasiswa = User::select(['id', 'id_angkatan'])->where('email',$mahasiswa)->first();//  AMBIL ID MAHASISWA
        $data_block = Master_block::select('id')->where('id_angkatan',$data_mahasiswa->id_angkatan)->get();
        $value = 0;
        $result = array();// ARRAY RESULT
        $waktu = date("Y-m-d H:i:s");
        $hari_besok = mktime (0,0,0, date("m"), date("d")+1,date("Y"));
        $tanggal_besok = date('Y-m-d',$hari_besok );// TANGGAL BESOK

        $array_block = array();
        foreach ($data_block as $data_blocks) {
          array_push($array_block, $data_blocks->id);
        }

        $penjadwalans = Penjadwalan::select('penjadwalans.id AS id_jadwal', 'penjadwalans.id_block AS id_block', 'penjadwalans.id_ruangan AS id_ruangan', 'penjadwalans.tipe_jadwal AS tipe_jadwal', 'penjadwalans.tanggal AS tanggal',  'penjadwalans.waktu_mulai AS waktu_mulai',  'penjadwalans.waktu_selesai AS waktu_selesai', 'master_mata_kuliahs.nama_mata_kuliah', 'master_ruangans.nama_ruangan AS ruangan', 'master_ruangans.longitude AS longitude', 'master_ruangans.latitude AS latitude', 'master_ruangans.batas_jarak_absen AS batas_jarak_absen')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH, DAN RUANGAN

                        ->leftJoin('master_mata_kuliahs','penjadwalans.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','penjadwalans.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                        ->whereIn('penjadwalans.id_block', $array_block)
                        //WHERE ID BLOK = ID BLOK USER LOGIN
                        ->where('penjadwalans.tanggal', '=', $tanggal_besok)
                        // JADWAL YANG TAMPIL ADALAH JADWAL HARI INI
                        ->where(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('penjadwalans.status_jadwal',0)
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA
                        ->where(function($query) use ($search){// search
                            $query->orWhere('penjadwalans.tanggal','LIKE',$search.'%')// OR LIKE TANGGAL
                                  ->orWhere(DB::raw('DATE_FORMAT(penjadwalans.tanggal, "%d/%m/%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d/m/y
                                  ->orWhere(DB::raw('DATE_FORMAT(penjadwalans.tanggal, "%d-%m-%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d-m-y
                                  ->orWhere('penjadwalans.waktu_mulai','LIKE',$search.'%')// OR LIKE WAKTU MULAI
                                  ->orWhere('penjadwalans.tipe_jadwal','LIKE',$search.'%')// OR LIKE WAKTU MULAI
                                  ->orWhere('master_mata_kuliahs.nama_mata_kuliah','LIKE',$search.'%')// OR LIKE NAMA MATA KULIAH
                                  ->orWhere('master_ruangans.nama_ruangan','LIKE',$search.'%');  //OR LIKE NAMA RUANGAN
                        }) 
                        ->orderBy(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->get();

      foreach ($penjadwalans as $list_jadwal_mahasiswa) {// FOREACH
        if ($list_jadwal_mahasiswa['nama_mata_kuliah'] == "") {
          $mata_kuliah = "-";
        }
        else{
          $mata_kuliah = $list_jadwal_mahasiswa['nama_mata_kuliah'];
        }

        $value = $value + 1;
        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_mahasiswa['tanggal']),// TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_mahasiswa['waktu_mulai'] ." - " . $list_jadwal_mahasiswa['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $mata_kuliah,// MATA KULIAH
                        'tipe_jadwal' => $list_jadwal_mahasiswa['tipe_jadwal'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_mahasiswa['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_mahasiswa['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_mahasiswa['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_mahasiswa['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_mahasiswa['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_mahasiswa['batas_jarak_absen'] // LONGITUDE


                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

      // DATA YANG DIKEMBALIKAN  BERUPA JSON
      return json_encode(array('value' => '1' , 'result'=>$result));


    }
// END CARI JADWAL MAHASISWA BESOK

    //DAFTAR JADWAL MAHASISWA LUSA
    public function jadwal_lusa(Request $request){

        $mahasiswa = $request->username;// MAHASISWA YANG LOGIN
        $data_mahasiswa = User::select(['id', 'id_angkatan'])->where('email',$mahasiswa)->first();//  AMBIL ID MAHASISWA
        $data_block = Master_block::select('id')->where('id_angkatan',$data_mahasiswa->id_angkatan)->get();
        $value = 0;
        $result = array();// ARRAY RESULT
        $waktu = date("Y-m-d H:i:s");
        $hari_besok = mktime (0,0,0, date("m"), date("d")+2,date("Y"));
        $tanggal_besok = date('Y-m-d',$hari_besok );// TANGGAL LUSA

        $array_block = array();
        foreach ($data_block as $data_blocks) {
          array_push($array_block, $data_blocks->id);
        }

        $penjadwalans = Penjadwalan::select('penjadwalans.id AS id_jadwal', 'penjadwalans.id_block AS id_block', 'penjadwalans.id_ruangan AS id_ruangan', 'penjadwalans.tipe_jadwal AS tipe_jadwal', 'penjadwalans.tanggal AS tanggal',  'penjadwalans.waktu_mulai AS waktu_mulai',  'penjadwalans.waktu_selesai AS waktu_selesai', 'master_mata_kuliahs.nama_mata_kuliah', 'master_ruangans.nama_ruangan AS ruangan', 'master_ruangans.longitude AS longitude', 'master_ruangans.latitude AS latitude', 'master_ruangans.batas_jarak_absen AS batas_jarak_absen')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH, DAN RUANGAN

                        ->leftJoin('master_mata_kuliahs','penjadwalans.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','penjadwalans.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                        ->whereIn('penjadwalans.id_block', $array_block)
                        //WHERE ID BLOK = ID BLOK USER LOGIN
                        ->where('penjadwalans.tanggal', '=', $tanggal_besok)
                        // JADWAL YANG TAMPIL ADALAH JADWAL HARI INI
                        ->where(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('penjadwalans.status_jadwal',0)
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA
                        ->orderBy(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->get();

      
      foreach ($penjadwalans as $list_jadwal_mahasiswa) {// FOREACH
        if ($list_jadwal_mahasiswa['nama_mata_kuliah'] == "") {
          $mata_kuliah = "-";
        }
        else{
          $mata_kuliah = $list_jadwal_mahasiswa['nama_mata_kuliah'];
        }

        $value = $value + 1;
        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_mahasiswa['tanggal']),// TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_mahasiswa['waktu_mulai'] ." - " . $list_jadwal_mahasiswa['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $mata_kuliah,// MATA KULIAH
                        'tipe_jadwal' => $list_jadwal_mahasiswa['tipe_jadwal'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_mahasiswa['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_mahasiswa['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_mahasiswa['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_mahasiswa['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_mahasiswa['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_mahasiswa['batas_jarak_absen'] // LONGITUDE


                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

     // DATA YANG DIKIRIM BERUPA JSON
      return json_encode(array('value' => $value , 'result'=>$result));

    }
    //DAFTAR JADWAL MAHASISWA LUSA


// CARI JADWAL MAHASISWA LUSA
    public function search_jadwal_mahasiswa_lusa(Request $request){

        $search = $request->search;// REQUEST SEARCH
        $mahasiswa = $request->username;// MAHASISWA YANG LOGIN
        $data_mahasiswa = User::select(['id', 'id_angkatan'])->where('email',$mahasiswa)->first();//  AMBIL ID MAHASISWA
        $data_block = Master_block::select('id')->where('id_angkatan',$data_mahasiswa->id_angkatan)->get();
        $value = 0;
        $result = array();// ARRAY RESULT
        $waktu = date("Y-m-d H:i:s");
        $hari_besok = mktime (0,0,0, date("m"), date("d")+2,date("Y"));
        $tanggal_besok = date('Y-m-d',$hari_besok );// TANGGAL LUSA

        $array_block = array();
        foreach ($data_block as $data_blocks) {
          array_push($array_block, $data_blocks->id);
        }

        $penjadwalans = Penjadwalan::select('penjadwalans.id AS id_jadwal', 'penjadwalans.id_block AS id_block', 'penjadwalans.id_ruangan AS id_ruangan', 'penjadwalans.tipe_jadwal AS tipe_jadwal', 'penjadwalans.tanggal AS tanggal',  'penjadwalans.waktu_mulai AS waktu_mulai',  'penjadwalans.waktu_selesai AS waktu_selesai', 'master_mata_kuliahs.nama_mata_kuliah', 'master_ruangans.nama_ruangan AS ruangan', 'master_ruangans.longitude AS longitude', 'master_ruangans.latitude AS latitude', 'master_ruangans.batas_jarak_absen AS batas_jarak_absen')// DATA YANG DIAMBIL TANGGAL,WAKTU MULAI, WAKTU SELESAI, NAMA MATA KULIAH, DAN RUANGAN

                        ->leftJoin('master_mata_kuliahs','penjadwalans.id_mata_kuliah','=','master_mata_kuliahs.id')
                        //LEFT JOIN KE TABLE MATA KULIAH
                        ->leftJoin('master_ruangans','penjadwalans.id_ruangan','=','master_ruangans.id')
                        // LEFT JOIN MASTER RUANGAN
                        ->whereIn('penjadwalans.id_block', $array_block)
                        //WHERE ID BLOK = ID BLOK USER LOGIN
                        ->where('penjadwalans.tanggal', '=', $tanggal_besok)
                        // JADWAL YANG TAMPIL ADALAH JADWAL HARI INI
                        ->where(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_selesai)'),'>=',$waktu)
                        // JADWAL YANG DIAMBIL ADALAH JADWAL YANG AKAN DATANG, JADWAL YANG SUDAH LEWAT TIDAK AKAN TAMPIL
                        ->where('penjadwalans.status_jadwal',0)
                        // YANG DITAMPILKAN HANYA JADWAL YANG BELUM TERLAKSANA
                        ->where(function($query) use ($search){// search
                            $query->orWhere('penjadwalans.tanggal','LIKE',$search.'%')// OR LIKE TANGGAL
                                  ->orWhere(DB::raw('DATE_FORMAT(penjadwalans.tanggal, "%d/%m/%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d/m/y
                                  ->orWhere(DB::raw('DATE_FORMAT(penjadwalans.tanggal, "%d-%m-%Y")'),'LIKE',$search.'%')// OR LIKE FORMAT TANGGAL d-m-y
                                  ->orWhere('penjadwalans.waktu_mulai','LIKE',$search.'%')// OR LIKE WAKTU MULAI
                                  ->orWhere('penjadwalans.tipe_jadwal','LIKE',$search.'%')// OR LIKE WAKTU MULAI
                                  ->orWhere('master_mata_kuliahs.nama_mata_kuliah','LIKE',$search.'%')// OR LIKE NAMA MATA KULIAH
                                  ->orWhere('master_ruangans.nama_ruangan','LIKE',$search.'%');  //OR LIKE NAMA RUANGAN
                        }) 
                        ->orderBy(DB::raw('CONCAT(penjadwalans.tanggal, " ", penjadwalans.waktu_mulai)', 'ASC'))
                        // DITAMPILKAN BERDASARKAN WAKTU TERDEKAT
                        ->get();

      foreach ($penjadwalans as $list_jadwal_mahasiswa) {// FOREACH
        if ($list_jadwal_mahasiswa['nama_mata_kuliah'] == "") {
          $mata_kuliah = "-";
        }
        else{
          $mata_kuliah = $list_jadwal_mahasiswa['nama_mata_kuliah'];
        }

        $value = $value + 1;
        //ARRAY PUSH
        array_push($result, 
                  array('tanggal' => $this->tanggal_terbalik($list_jadwal_mahasiswa['tanggal']),// TANGGAL DI FORMAT=> Y/M/D
                        'waktu' => $list_jadwal_mahasiswa['waktu_mulai'] ." - " . $list_jadwal_mahasiswa['waktu_selesai'],// WAKTU MULAI DAN WAKTU SELESAI DIJADIKAN SATU STRING
                        'mata_kuliah' => $mata_kuliah,// MATA KULIAH
                        'tipe_jadwal' => $list_jadwal_mahasiswa['tipe_jadwal'],// MATA KULIAH
                        'nama_ruangan' => $list_jadwal_mahasiswa['ruangan'], // NAMA RUANGAN
                        'id_jadwal' => $list_jadwal_mahasiswa['id_jadwal'], // ID JADWAL
                        'id_ruangan' => $list_jadwal_mahasiswa['id_ruangan'], // ID RUANGAN
                        'latitude' => $list_jadwal_mahasiswa['latitude'], // LATITUDE
                        'longitude' => $list_jadwal_mahasiswa['longitude'], // LONGITUDE
                        'batas_jarak_absen' => $list_jadwal_mahasiswa['batas_jarak_absen'] // LONGITUDE


                        )// ARRAY
                  );// ARRAY PUSH

      }// END FOREACH

      // DATA YANG DIKEMBALIKAN  BERUPA JSON
      return json_encode(array('value' => '1' , 'result'=>$result));


    }
// END CARI JADWAL MAHASISWA LUSA

}//END CLASS
