<?php
$app=require '/qa/tests/Support/mysql-bootstrap.php';
use Illuminate\Support\Facades\DB;
use App\Services\OperationalAcademicData;
use App\Services\AcademicGradeSummary;
use App\Models\Mahasiswa;
$identity=Tests\Support\IsolatedMysql::verify($app);
$history=DB::table('krs_pengajuan_riwayat')->where('krs_id',1)->orderBy('versi')->get();
$grades=DB::table('nilai')->orderBy('id')->get();
$summary=app(AcademicGradeSummary::class)->forStudent(Mahasiswa::findOrFail(1));
$missing=0;
foreach(['users','program_studi','semester','mata_kuliah','dosen','mahasiswa','kelas_kuliah','krs','krs_detail','krs_pengajuan_riwayat','nilai','kehadiran'] as $table){
$missing+=DB::table($table)->whereNotIn('id',DB::table('qa_fixture_provenance')->where('table_name',$table)->select('row_id'))->count();
}
$roles=DB::table('users')->orderBy('id')->pluck('level','username')->all();
$checks=[
'history_versions'=>$history->pluck('versi')->all()===[1,2],
'history_statuses'=>$history->pluck('status')->all()===['ditolak','disetujui'],
'snapshot_sizes'=>$history->map(fn($h)=>count(json_decode($h->snapshot_kelas,true)['kelas']))->all()===[1,2],
'parent_approved'=>DB::table('krs')->where('id',1)->value('status')==='disetujui',
'grades_unchanged'=>$grades->pluck('nilai_angka')->all()===['90.00','78.00'],
'finalized_by_assigned_user'=>$grades->every(fn($g)=>$g->final_at!==null && (int)$g->difinalkan_oleh_user_id===2),
'ips_ipk'=>$summary['ips']===3.6 && $summary['ipk']===3.6,
'credits_unchanged'=>(int)DB::table('mata_kuliah')->where('id',1)->value('sks')===3,
'student_role_unchanged'=>($roles['qa_mahasiswa']??null)===1,
'demo_excluded'=>OperationalAcademicData::students()->count()===24 && DB::table('mahasiswa')->count()===25,
'provenance_complete'=>$missing===0,
'attendance_in_semester'=>DB::table('kehadiran')->join('krs_detail','krs_detail.id','=','kehadiran.krs_detail_id')->join('kelas_kuliah','kelas_kuliah.id','=','krs_detail.kelas_kuliah_id')->join('semester','semester.id','=','kelas_kuliah.semester_id')->where(fn($q)=>$q->whereColumn('kehadiran.tanggal','<','semester.tanggal_mulai')->orWhereColumn('kehadiran.tanggal','>','semester.tanggal_selesai'))->count()===0
];
$result=['identity'=>$identity,'checks'=>$checks,'summary'=>$summary,'roles'=>$roles,'provenance_rows'=>DB::table('qa_fixture_provenance')->count(),'pass'=>!in_array(false,$checks,true)];
echo json_encode($result,JSON_PRETTY_PRINT);
if(!$result['pass']) exit(1);

