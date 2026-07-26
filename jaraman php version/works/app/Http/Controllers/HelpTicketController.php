<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Models\HelpTicket;
use App\Http\Requests\HelpTicketRequest;
use App\Http\Resources\HelpTicketResource;

class HelpTicketController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Create Ticket
    |--------------------------------------------------------------------------
    */
    public function store(HelpTicketRequest $request)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('attachment')) {
                $data['attachment'] = upload_image("help-tickets", $data['attachment']);
            }

            $data['user_id'] = auth()->id();
            $data['status'] = "open";

            $ticket = HelpTicket::create($data);

            return response()->success('Ticket created successfully', new HelpTicketResource($ticket), 201);

        } catch (Exception $e) {
            report($e);

            return response()->errorResponse([
                'message' => 'Failed to create ticket'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | User Tickets List
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $tickets = HelpTicket::where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return response()->success(
            'Tickets fetched',
            HelpTicketResource::collection($tickets)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | View Single Ticket
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $ticket = HelpTicket::findOrFail($id);

        return response()->success(
            'Ticket details',
            new HelpTicketResource($ticket)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Update Status
    |--------------------------------------------------------------------------
    */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed'
        ]);

        $ticket = HelpTicket::findOrFail($id);

        $ticket->update([
            'status' => $request->status
        ]);

        return response()->success(
            'Status updated',
            new HelpTicketResource($ticket)
        );
    }
}
