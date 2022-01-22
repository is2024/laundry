<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{transaksi,customer};
use Auth;
use DB;
use Carbon\carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        if(Auth::check()){
          if (Auth::user()->auth === "Admin") {
              $masuk = transaksi::whereIN('status_order',['Process','Done','Delivery'])->count();
              $selesai = transaksi::where('status_order','Done')->count();
              $diambil = transaksi::where('status_order','Delivery')->count();
              $customer = customer::all();
              $sudahbayar = transaksi::where('status_payment','Success')->count();
              $belumbayar = transaksi::where('status_payment','Pending')->count();
              $incomeY = transaksi::where('status_payment','Success')->where('tahun',date('Y'))->sum('harga_akhir');
              $incomeM = transaksi::where('status_payment','Success')->where('tahun',date('Y'))->where('bulan', ltrim(date('m'),'0'))->sum('harga_akhir');
              $incomeYOld = transaksi::where('status_payment','Success')->where('tahun',date("Y",strtotime("-1 month")))->sum('harga_akhir');
              $incomeD = transaksi::where('status_payment','Success')->where('tahun',date('Y'))->where('bulan', ltrim(date('m'),'0'))->where('tgl',date('d'))->sum('harga_akhir');
              $incomeDOld = transaksi::where('status_payment','Success')->where('tahun',date('Y'))->where('bulan', ltrim(date('m'),'0'))->where('tgl',date("d",strtotime("-1 day")))->sum('harga_akhir');


              $data = DB::table("transaksis")
                  ->select("id" ,DB::raw("(COUNT(*)) as customer"))
                  ->orderBy('created_at')
                  ->groupBy(DB::raw("MONTH(created_at)"))
                  ->count();

              // Statistik Harian
              $hari = DB::table('transaksis')
              ->  select('tgl', DB::raw('count(id) AS jml'))
              ->  whereYear('created_at','=',date("Y", strtotime(now())))
              ->  whereMonth('created_at','=',date("m", strtotime(now())))
              ->  groupBy('tgl')
              ->  get();

              $tanggal = '';
              $batas =  31;
              $nilai = '';
              for($_i=1; $_i <= $batas; $_i++){
                  $tanggal = $tanggal . (string)$_i . ',';
                  $_check = false;
                  foreach($hari as $_data){
                      if((int)@$_data->tgl === $_i){
                          $nilai = $nilai . (string)$_data->jml . ',';
                          $_check = true;
                      }
                  }
                  if(!$_check){
                      $nilai = $nilai . '0,';
                  }
              }

              // Statistik Bulanan
              $bln = DB::table('transaksis')
              ->  select('bulan', DB::raw('count(id) AS jml'))
              ->  whereYear('created_at','=',date("Y", strtotime(now())))
              ->  whereMonth('created_at','=',date("m", strtotime(now())))
              ->  groupBy('bulan')
              ->  get();

              $bulans = '';
              $batas =  12;
              $nilaiB = '';
              for($_i=1; $_i <= $batas; $_i++){
                  $bulans = $bulans . (string)$_i . ',';
                  $_check = false;
                  foreach($bln as $_data){
                      if((int)@$_data->bulan === $_i){
                          $nilaiB = $nilaiB . (string)$_data->jml . ',';
                          $_check = true;
                      }
                  }
                  if(!$_check){
                      $nilaiB = $nilaiB . '0,';
                  }
              }

              return view('modul_admin.index')
                  ->  with('data', $data)
                  ->  with('masuk',$masuk)
                  ->  with('selesai',$selesai)
                  ->  with('customer', $customer)
                  ->  with('sudahbayar', $sudahbayar)
                  ->  with('belumbayar', $belumbayar)
                  ->  with('_tanggal', substr($tanggal, 0,-1))
                  ->  with('_nilai', substr($nilai, 0, -1))
                  ->  with('_bulan', substr($bulans, 0,-1))
                  ->  with('_nilaiB', substr($nilaiB, 0, -1))
                  ->  with('diambil',$diambil)
                  ->  with('incomeY',$incomeY)
                  ->  with('incomeM',$incomeM)
                  ->  with('incomeYOld',$incomeYOld)
                  ->  with('incomeD',$incomeD)
                  ->  with('incomeDOld',$incomeDOld);

          } elseif(Auth::user()->auth === "Karyawan") {
              $masuk = transaksi::whereIN('status_order',['Process','Done','Delivery'])->where('user_id',auth::user()->id)->count();
              $selesai = transaksi::where('status_order','Deone')->where('user_id',auth::user()->id)->count();
              $diambil = transaksi::where('status_order','Delivery')->where('user_id',auth::user()->id)->count();
              $customer = customer::where('user_id',auth::user()->id)->get();
              $kgToday = transaksi::where('user_id',Auth::id())->where('tahun',date('Y'))->where('bulan', ltrim(date('m'),'0'))->where('tgl',date('d'))->sum('kg');
              $kgTodayOld = transaksi::where('user_id',Auth::id())->where('tahun',date('Y'))->where('bulan', ltrim(date('m'),'0'))->where('tgl',date("d",strtotime("-1 day")))->sum('kg');
              $incomeM = transaksi::where('user_id',Auth::id())->where('status_payment','Success')->where('tahun',date('Y'))->where('bulan', ltrim(date('m'),'0'))->sum('harga_akhir');
              $incomeMOld = transaksi::where('user_id',Auth::id())->where('status_payment','Success')->where('tahun',date('Y'))->where('bulan', ltrim(date('m',strtotime("-1 month")),'0'))->sum('harga_akhir');

              $persen = ($incomeM - $incomeMOld) / $incomeM * 100;

              // Statistik Bulanan
              $bln = DB::table('transaksis')
              ->  select('bulan', DB::raw('count(id) AS jml'))
              ->  whereYear('created_at','=',date("Y", strtotime(now())))
              ->  whereMonth('created_at','=',date("m", strtotime(now())))
              ->  groupBy('bulan')
              ->  get();

              $bulans = '';
              $batas =  12;
              $nilaiB = '';
              for($_i=1; $_i <= $batas; $_i++){
                  $bulans = $bulans . (string)$_i . ',';
                  $_check = false;
                  foreach($bln as $_data){
                      if((int)@$_data->bulan === $_i){
                          $nilaiB = $nilaiB . (string)$_data->jml . ',';
                          $_check = true;
                      }
                  }
                  if(!$_check){
                      $nilaiB = $nilaiB . '0,';
                  }
              }

              return view('karyawan.index')
                  ->  with('diambil', $diambil)
                  ->  with('masuk',$masuk)
                  ->  with('selesai',$selesai)
                  ->  with('customer', $customer)
                  ->  with('kgToday', $kgToday)
                  ->  with('kgTodayOld', $kgTodayOld)
                  ->  with('incomeM',$incomeM)
                  ->  with('incomeMOld',$incomeMOld)
                  ->  with('persen',$persen)
                  ->  with('_bulan', substr($bulans, 0,-1))
                  ->  with('_nilaiB', substr($nilaiB, 0, -1));

          }else{
              Auth::logout();
          }
        }
    }

}
