<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index(Request $request) {
        if($request->p != 'm3d1ana33') {
            return '<h3>Access denied</h3>';
        }

        $connections = DB::table('connection')->where('license', '!=', 'test')->get()->toArray();

        $data = [];

        $curTime = time();
        $now = Carbon::now();

        foreach ($connections as $connection) {
            Config::set('database.connections.smart_mektep_db.database', $connection->database);
            Config::set('database.connections.smart_mektep_db.username', $connection->host_user);
            Config::set('database.connections.smart_mektep_db.password', $connection->host_password);

            DB::purge('smart_mektep_db');

            $passLog = DB::connection('smart_mektep_db')
                ->table('loc_pass_log')
                ->select('created_at')
                ->orderBy('created_at', 'desc')
                ->first();

            $foodLog = DB::connection('smart_mektep_db')
                ->table('loc_food_log')
                ->select('created_at')
                ->orderBy('created_at', 'desc')
                ->first();

            $elibLog = DB::connection('smart_mektep_db')
                ->table('loc_elib_log')
                ->select('created_at')
                ->orderBy('created_at', 'desc')
                ->first();

            $passDiff = null;
            if($passLog) {
                $targetDate = Carbon::parse($passLog->created_at);
                $diff = $now->diffForHumans($targetDate, [
                    'parts' => 2, // Выводить только 2 наибольшие единицы времени (месяцы и дни)
                    'short' => true // Использовать короткие наименования единиц времени (мес. - месяцы, дн. - дни и т.д.)
                ]);

                $passDiff = str_replace('после', '', $diff);
            }

            $foodDiff = null;
            if($foodLog) {
                $targetDate = Carbon::parse($foodLog->created_at);
                $diff = $now->diffForHumans($targetDate, [
                    'parts' => 2, // Выводить только 2 наибольшие единицы времени (месяцы и дни)
                    'short' => true // Использовать короткие наименования единиц времени (мес. - месяцы, дн. - дни и т.д.)
                ]);

                $foodDiff = str_replace('после', '', $diff);
            }


            if($passLog && strtotime($passLog->created_at) > ($curTime - 3600)) {
                $passColor = 'table-success';
            }
//            else if($passLog && strtotime($passLog->created_at) > ($curTime - 7200)) {
//                $passColor = 'table-warning';
//            }
            else {
                $passColor = 'table-danger';
            }

            if($foodLog && strtotime($foodLog->created_at) > ($curTime - 43200)) {
                $foodColor = 'table-success';
            }
//            else if($foodLog && strtotime($foodLog->created_at) > ($curTime - 72000)) {
//                $foodColor = 'table-warning';
//            }
            else {
                $foodColor = 'table-danger';
            }

            $data[] = [
                "mektepName" => $connection->owner,
                "license" => $connection->license,
                "status" => $connection->status,
                "mektepId" => $connection->mektep_id,
                "passLog" => $passLog?->created_at,
                "foodLog" => $foodLog?->created_at,
                "elibLog" => $elibLog?->created_at,
                "passDiff" => $passDiff,
                "foodDiff" => $foodDiff,
                "passColor" => $passColor,
                "foodColor" => $foodColor,
            ];
        }

        usort($data, function($a, $b) {
            $dateA = strtotime($a['passLog']);
            $dateB = strtotime($b['passLog']);
            return $dateB - $dateA;
        });

        return view('monitoring', ["data" => $data]);
    }
}
