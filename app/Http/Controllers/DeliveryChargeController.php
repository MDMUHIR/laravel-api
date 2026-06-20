<?php

namespace App\Http\Controllers;

use App\Models\DeliveryCharge;
use Illuminate\Http\Request;

class DeliveryChargeController extends Controller
{
    public function getDeliveryCharges(Request $request)
    {
        $charges = DeliveryCharge::all();
        return $this->success('Delivery charges retrieved successfully', $charges);
    }

    public function addDeliveryCharge(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'charge' => 'required|numeric|min:0',
            'minimum_order' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $charge = DeliveryCharge::create([
            'name' => $request->name,
            'charge' => $request->charge,
            'minimum_order' => $request->minimum_order,
            'status' => $request->boolean('status', true),
        ]);

        return $this->success('Delivery charge added successfully', $charge);
    }

    public function updateDeliveryCharge(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:delivery_charges,id',
            'name' => 'required|string|max:255',
            'charge' => 'required|numeric|min:0',
            'minimum_order' => 'nullable|numeric|min:0',
            'status' => 'nullable|boolean',
        ]);

        $charge = DeliveryCharge::find($request->id);
        if (!$charge) {
            return $this->error('Delivery charge not found', 404);
        }

        $charge->update([
            'name' => $request->name,
            'charge' => $request->charge,
            'minimum_order' => $request->minimum_order,
            'status' => $request->boolean('status', $charge->status),
        ]);

        return $this->success('Delivery charge updated successfully', $charge);
    }

    public function deleteDeliveryCharge(Request $request, $id)
    {
        $charge = DeliveryCharge::find($id);
        if (!$charge) {
            return $this->error('Delivery charge not found', 404);
        }

        $charge->delete();

        return $this->success('Delivery charge deleted successfully');
    }
}
