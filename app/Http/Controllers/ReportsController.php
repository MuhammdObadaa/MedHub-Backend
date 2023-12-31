<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AuthMiddleware;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\CartResource;
use App\Http\Resources\UserResource;
use App\Models\Cart;
use App\Models\User;
use Carbon\Carbon;

class ReportsController extends Controller
{

    private function formatAdminReport($start, $end)
    {
        $ar = (request()->header('lang') == 'ar');

        $newUsers = User::whereBetween('created_at', [$start, $end])->count();
        $newUsersInfo = UserResource::collection(User::whereBetween('created_at', [$start, $end])->get());
        $newOrders = Cart::whereBetween('created_at', [$start, $end])->count();
        $inPreparationOrders = Cart::whereBetween('created_at', [$start, $end])->where('status', 'in preparation')->count();
        $gettingDeliveredOrders = Cart::whereBetween('created_at', [$start, $end])->where('status', 'getting delivered')->count();
        $deliveredOrders = Cart::whereBetween('created_at', [$start, $end])->where('status', 'delivered')->count();
        $refusedOrders = Cart::whereBetween('created_at', [$start, $end])->where('status', 'refused')->count();
        //another way to some pivot attributes in laravel, look totalMed in userStat
        $soldMedicines = Cart::whereBetween('carts.created_at', [$start, $end])->where('payed', 1)->join('cart_medicine', 'id', '=', 'cart_medicine.cart_id')->sum('quantity');
        $totalIncome = Cart::whereBetween('carts.created_at', [$start, $end])->where('payed', 1)->sum('bill');
        $totalProfit = Cart::whereBetween('carts.created_at', [$start, $end])->where('payed', 1)->sum('profit');

        $soldCategoriesPercentages = [];
        $carts = Cart::whereBetween('created_at', [$start, $end])->where('payed', 1)->get();
        foreach ($carts as $cart) {
            $medicines = $cart->medicines()->get();
            foreach ($medicines as $medicine) {
                if ($ar) $name = $medicine->category()->first()->ar_name;
                else $name = $medicine->category()->first()->name;
                $soldCategoriesPercentages[$name] = ($soldCategoriesPercentages[$name] ?? 0) + $medicine->pivot->quantity;
            }
        }
        foreach ($soldCategoriesPercentages as &$percentage) {
            $percentage = (float) number_format(($percentage * 100.0) / $soldMedicines, 2);
        }

        $carts = Cart::whereBetween('created_at', [$start, $end])->where('payed', 1)->get();
        $IncomeChart = $carts->groupBy(function ($cart) {
            return $cart->created_at->format('Y-m-d');
        })->map(function ($group) {
            return ($group->sum('bill'));
        });
        $ProfitChart = $carts->groupBy(function ($cart) {
            return $cart->created_at->format('Y-m-d');
        })->map(function ($group) {
            return ($group->sum('profit'));
        });

        $ordersInfo = CartResource::collection(Cart::whereBetween('created_at', [$start, $end])->where('payed', 1)->get());

        $data = [
            'from' => $start->format('Y-m-d'),
            'to' => $end->format('Y-m-d'),
            'joined users' => $newUsers,
            'joined users info' => $newUsersInfo,
            'orders count' => $newOrders,
            'in preparation orders' => $inPreparationOrders,
            'getting delivered orders' => $gettingDeliveredOrders,
            'delivered orders' => $deliveredOrders,
            'refused orders' => $refusedOrders,
            'sold medicines count' => (int) $soldMedicines,
            'categories percentages for sold medicines' => $soldCategoriesPercentages,
            'total income' => (int) $totalIncome,
            'total profit' => (int) $totalProfit,
            'income chart' => $IncomeChart,
            'profit chart' => $ProfitChart,
            'payed orders info' => $ordersInfo,
        ];

        return $data;
    }

    private function formatUserReport($start, $end)
    {
        $ar = (request()->header('lang') == 'ar');
        $user = AuthMiddleware::getUser();
        //$user = User::find(1);

        $totalOrders = $user->carts()->whereBetween('created_at', [$start, $end])->count();
        $refusedOrders = $user->carts()->whereBetween('created_at', [$start, $end])->where('status', 'refused')->count();
        $PreparingOrders = $user->carts()->whereBetween('created_at', [$start, $end])->where('status', 'in preparation')->count();
        $deliveredOrders = $user->carts()->whereBetween('created_at', [$start, $end])->where('status', 'delivered')->count();
        $gettingDeliveredOrders = $user->carts()->whereBetween('created_at', [$start, $end])->where('status', 'getting delivered')->count();
        $totalBill = (int) $user->carts()->where('payed', 1)->whereBetween('created_at', [$start, $end])->sum('bill');

        $carts = $user->carts()->whereBetween('created_at', [$start, $end])->where('payed', 1)->get();
        $cartsChart = $carts->groupBy(function ($cart) {
            return $cart->created_at->format('Y-m-d');
        })->map(function ($group) {
            return ($group->sum('bill'));
        });

        $carts = CartResource::collection($user->carts()->whereBetween('created_at', [$start, $end])->get());

        $data = [
            'from' => $start->format('Y-m-d'),
            'to' => $end->format('Y-m-d'),
            'total orders' => $totalOrders,
            'refused orders' => $refusedOrders,
            'in preparation orders' => $PreparingOrders,
            'delivered orders' => $deliveredOrders,
            'getting delivered orders' => $gettingDeliveredOrders,
            'total payment' => $totalBill,
            'carts chart' => $cartsChart,
            'carts' => $carts
        ];

        return $data;
    }

    //https://laraveldaily.com/post/laravel-dompdf-generate-simple-invoice-pdf-with-images-css
    public function pdfAdminReport($year1, $month1, $day1, $year2, $month2, $day2)
    {
        $data = $this->formatAdminReport(
            Carbon::create($year1, $month1, $day1),
            Carbon::create($year2, $month2, $day2, 23, 59, 59)
        );
        $admin = AuthMiddleware::getUser();
        $data['user'] = $admin->name;

        $fileName = 'MedHub' . date('Y-m-d') . 'ReportToAdmin' . $admin->id . '.pdf';

        $data['payed orders info'] = $data['payed orders info']->toArray(Request());

        $pdf = Pdf::loadView('adminReport', ['data' => $data]);

        Storage::put(('public/app/' . $fileName), $pdf->download()->getOriginalContent());

        //return $pdf->download();
        //return $pdf->stream('AdminReport');
        return response()->json(['file' => url('storage/app', $fileName)]);
    }

    public function pdfUserReport($year1, $month1, $day1, $year2, $month2, $day2)
    {
        $data = $this->formatUserReport(
            Carbon::create($year1, $month1, $day1),
            Carbon::create($year2, $month2, $day2, 23, 59, 59)
        );

        $user = AuthMiddleware::getUser();
        $data['user'] = $user->name;

        $fileName = 'MedHub' . date('Y-m-d') . 'ReportToUser' . $user->id . '.pdf';

        $pdf = Pdf::loadView('userReport', ['data' => $data]);

        Storage::put('public/app/' . $fileName, $pdf->download()->getOriginalContent());

        return response()->json(['file' => url('storage/app', $fileName)]);
    }

    public function adminReport($year1, $month1, $day1, $year2, $month2, $day2)
    {
        $lang = $this->lang();

        $data = $this->formatAdminReport(
            Carbon::create($year1, $month1, $day1),
            Carbon::create($year2, $month2, $day2, 23, 59, 59)
        );

        $message['ar'] = 'تم عرض التقرير بنجاح';
        $message['en'] = 'report returned successfully!';

        $data['message'] = $message[$lang];

        return response()->json($data);
    }

    public function userReport($year1, $month1, $day1, $year2, $month2, $day2)
    {
        $lang = $this->lang();

        $data = $this->formatUserReport(
            Carbon::create($year1, $month1, $day1),
            Carbon::create($year2, $month2, $day2, 23, 59, 59)
        );

        $message['ar'] = 'تم عرض التقرير بنجاح';
        $message['en'] = 'report returned successfully!';

        $data['message'] = $message[$lang];

        return response()->json($data);
    }
}
