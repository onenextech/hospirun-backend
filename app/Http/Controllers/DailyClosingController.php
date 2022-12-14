<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\DailyClosing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyClosingController extends Controller
{

    /** Get DailyClosing List
        * @OA\Get(
        * path="/api/daily-closings",
        * operationId="dailyClosingList",
        * tags={"DailyClosing"},
        * summary="List",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"per_page", "page", "sort_by"},
        *               @OA\Property(property="q", type="string"),
        *               @OA\Property(property="per_page", type="integer"),
        *               @OA\Property(property="page", type="integer"),
        *               @OA\Property(property="sort_by", type="string"),
        *               @OA\Property(property="sort_desc", type="boolean"),
        *               @OA\Property(property="closing_dates", type="object"),
        *            ),
        *        ),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function all(Request $request)
    {
        $total = 0;
        $records = null;

        // validate incoming request
        $this->validate($request, [
            'per_page' => 'required|integer',
            'page' => 'required|integer',
            'sort_by' => 'required|string'
        ]);

        $q = $request->input('q');
        $perPage = $request->input('per_page');
        $page = $request->input('page');
        $sortBy = $request->input('sort_by');

        $sortDesc = $request->input('sort_desc');
        if($sortDesc == "true") {
            $sortOrder = "desc";
        }
        else {
            $sortOrder = "asc";
        }

        $closingDates = $request->input('closing_dates');

        $skip = ($page-1) * $perPage;

        $records = DailyClosing::where(function ($query) use($closingDates) {
            if($closingDates !== null) {
                $from = $closingDates['from'];
                $to = $closingDates['to'];
                $query->whereBetween('date', [$from, $to]);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('remark', 'like', "%{$q}%");
            }
        })
        ->orderBy($sortBy, $sortOrder)
        ->skip($skip)->take($perPage)
        ->get();

        $total = DailyClosing::where(function ($query) use($closingDates) {
            if($closingDates !== null) {
                $from = $closingDates['from'];
                $to = $closingDates['to'];
                $query->whereBetween('date', [$from, $to]);
            }
        })
        ->where(function ($query) use($q) {
            if($q !== "") {
                $query->where('remark', 'like', "%{$q}%");
            }
        })
        ->get()->count();

        $data['daily_closings'] = $records;
        $data['total'] = $total;

        if(!count($data)){
            return $this->response('no_data');
        }
        return $this->response('done', $data);
    }

    /** Get DailyClosing
        * @OA\Get(
        * path="/api/daily_closings/{id}",
        * operationId="dailyClosingGet",
        * tags={"DailyClosing"},
        * summary="Get",
        *     @OA\Parameter(
        *       in="path",
        *       name="id",
        *       required=true,
        *       @OA\Schema(type="integer"),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function get($id)
    {
        $data = DailyClosing::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        return $this->response('done', $data);
    }

    /** Create DailyClosing
        * @OA\Post(
        * path="/api/daily_closings",
        * operationId="dailyClosingCreate",
        * tags={"DailyClosing"},
        * summary="Create",
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               required={"date", "opening_balance", "deposit_total", "bill_total", "grand_total", "actual_amount", "adjusted_amount" },
        *               @OA\Property(property="date", type="string"),
        *               @OA\Property(property="opening_balance", type="number"),
        *               @OA\Property(property="deposit_total", type="number"),
        *               @OA\Property(property="bill_total", type="number"),
        *               @OA\Property(property="grand_total", type="number"),
        *               @OA\Property(property="actual_amount", type="number"),
        *               @OA\Property(property="adjusted_amount", type="number"),
        *               @OA\Property(property="remark", type="string"),
        *            ),
        *        ),
        *     ),
        *     @OA\Response(
        *          response=201,
        *          description="Success"
        *     ),
        * )
    */
    public function add(Request $request)
    {
        //validate incoming request
        $this->validate($request, [
            'date' => 'required|string|max:255',
            'opening_balance' => 'required|numeric',
            'deposit_total' => 'required|numeric',
            'bill_total' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'actual_amount' => 'required|numeric',
            'adjusted_amount' => 'required|numeric',
            'remark' => 'string',
        ]);

        try {
            $data = $request->only(['date', 'opening_balance', 'deposit_total', 'bill_total', 'grand_total', 'actual_amount', 'adjusted_amount', 'remark']);
            $data['created_by'] = Auth::user()->id; // track who is creating this
            $result = DailyClosing::insertGetId($data);
            $data = array('id'=> $result) + $data; //add generated id infront of response data array
        } catch (\Exception $e) {
            //return error message
            return $this->response('not_valid', $e);
        }

        //return successful response
        return $this->response('created', $data);
    }

    /** Update DailyClosing
        * @OA\Put(
        * path="/api/daily_closings/{id}",
        * operationId="dailyClosingUpdate",
        * tags={"DailyClosing"},
        * summary="Update",
        *     @OA\Parameter(
        *       in="path",
        *       name="id",
        *       required=true,
        *       @OA\Schema(type="integer"),
        *     ),
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *            mediaType="application/json",
        *            @OA\Schema(
        *               @OA\Property(property="date", type="string"),
        *               @OA\Property(property="opening_balance", type="number"),
        *               @OA\Property(property="deposit_total", type="number"),
        *               @OA\Property(property="bill_total", type="number"),
        *               @OA\Property(property="grand_total", type="number"),
        *               @OA\Property(property="actual_amount", type="number"),
        *               @OA\Property(property="adjusted_amount", type="number"),
        *               @OA\Property(property="remark", type="string"),
        *            ),
        *        ),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function put($id, Request $request)
    {
        $this->validate($request, [
            'date' => 'string|max:255',
            'opening_balance' => 'numeric',
            'deposit_total' => 'numeric',
            'bill_total' => 'numeric',
            'grand_total' => 'numeric',
            'actual_amount' => 'numeric',
            'adjusted_amount' => 'numeric',
            'remark' => 'string',
        ]);

        $newData = $request->only(['date', 'opening_balance', 'deposit_total', 'bill_total', 'grand_total', 'actual_amount', 'adjusted_amount', 'remark']);

        DB::beginTransaction();

        try {

            $data = DailyClosing::find($id);
            if(is_null($data)) {
                return $this->response('not_found');
            }

            $newData['updated_at'] = now()->toDateTimeString(); // track when updated
            $newData['updated_by'] = Auth::user()->id;  // track who is updating this

            $data->update($newData);
            DB::commit();
        }
        catch(\Exception $e) {
            DB::rollBack();
            //return error message
            return $this->response('not_valid', $e);
        }

        return $this->response('done', $data);
    }

    /** Delete DailyClosing
        * @OA\Delete(
        * path="/api/daily_closings/{id}",
        * operationId="dailyClosingDelete",
        * tags={"DailyClosing"},
        * summary="Delete",
        *     @OA\Parameter(
        *       in="path",
        *       name="id",
        *       required=true,
        *       @OA\Schema(type="integer"),
        *     ),
        *     @OA\Response(
        *          response=200,
        *          description="Success"
        *     ),
        * )
    */
    public function remove($id)
    {
        $data = DailyClosing::find($id);
        if(is_null($data)) {
            return $this->response('not_found');
        }
        try {
            $data['deleted_by'] = Auth::user()->id; // track who is deleting this
            $data->save(); // save before delete for tracking who is deleting
            $data->delete(); // soft delete
        }
        catch (\Exception $e) {
            return $this->response('not_valid', $e);
        }
        return $this->response('done', ["id"=>$id]);
    }

}
