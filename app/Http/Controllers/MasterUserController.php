<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Html\Builder;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Facades\DB;
use App\User;
use Auth;
use Session;

class MasterUserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Builder $htmlBuilder)
    {
        //
        if ($request->ajax()) {
            # code...
            $master_users = User::select(['id','name', 'email', 'password','no_hp','alamat','status']);
            return Datatables::of($master_users)
            ->addColumn('action', function($master_user){
                    return view('datatable._action', [
                        'model'     => $master_user,
                        'form_url'  => route('master_users.destroy', $master_user->id),
                        'edit_url'  => route('master_users.edit', $master_user->id),
                        'confirm_message'   => 'Yakin Mau Menghapus User ' . $master_user->name . '?'
                        ]);
                })
            ->addColumn('konfirmasi', function($user_konfirmasi){
                    return view('master_users._action', [
                        'model'     => $user_konfirmasi,
                        'konfirmasi_url' => route('master_users.konfirmasi', $user_konfirmasi->id),
                        'no_konfirmasi_url' => route('master_users.no_konfirmasi', $user_konfirmasi->id),
                        ]);
                })->make(true);
        }
        $html = $htmlBuilder
        ->addColumn(['data' => 'name', 'name' => 'name', 'title' => 'Nama'])
        ->addColumn(['data' => 'email', 'name' => 'email', 'title' => 'Username']) 
        ->addColumn(['data' => 'no_hp', 'name' => 'no_hp', 'title' => 'Nomor Hp'])
        ->addColumn(['data' => 'alamat', 'name' => 'alamat', 'title' => 'Alamat'])
        ->addColumn(['data' => 'konfirmasi', 'name' => 'konfirmasi', 'title' => 'Konfirmasi', 'searchable'=>false])
        ->addColumn(['data' => 'action', 'name' => 'action', 'title' => '', 'orderable' => false, 'searchable'=>false]);

        return view('master_users.index')->with(compact('html'));
    }


    public function konfirmasi($id){ 

            $master_users = User::find($id);   
            $master_users->status = 1;
            $master_users->save();  

        Session::flash("flash_notification", [
            "level"=>"success",
            "message"=>"User Berhasil Di konfirmasi"
        ]);
 
        return redirect()->route('master_users.index');
    } 


    public function no_konfirmasi($id){ 

            $master_users = User::find($id);   
            $master_users->status = 0;
            $master_users->save();  

        Session::flash("flash_notification", [
            "level"=>"danger",
            "message"=>"User Tidak Di konfirmasi"
        ]);
 
        return redirect()->route('master_users.index');
    } 


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $master_users = User::find($id);
        return view('master_users.edit')->with(compact('master_users'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
         $this->validate($request, [
            'name'   => 'required',
            'email'     => 'required|unique:users,email,' .$id,
            'no_hp'    => 'required',
            'alamat'    => 'required'
            ]);

        User::where('id', $id) ->update([ 
            'name' =>$request->name,
            'email'=>$request->email,
            'no_hp'=>$request->no_hp,
            'alamat'=>$request->alamat
            ]);

        Session::flash("flash_notification", [
            "level"=>"success",
            "message"=>"Berhasil Mengubah User $request->name"
            ]);

        return redirect()->route('master_users.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
