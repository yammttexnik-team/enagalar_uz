<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Services\Ai\ListingParseException;
use App\Services\Ai\ListingParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** "Matnni joylash (AI)": Telegram e'lon matnidan forma maydonlarini ajratadi. Hech narsa saqlanmaydi. */
class AiListingController extends Controller
{
    public function __construct(private readonly ListingParser $parser) {}

    public function parse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in([ListingParser::TYPE_NANNY, ListingParser::TYPE_VACANCY])],
            'text' => ['required', 'string', 'min:20', 'max:4000'],
        ]);

        // Anketa — enaga roli, vakansiya — ota-ona roli.
        $role = $data['type'] === ListingParser::TYPE_NANNY ? Role::Nanny : Role::Parent;
        abort_unless($request->user()->hasRole($role->value), 403, "Bu amal uchun '{$role->value}' roli kerak.");

        try {
            $fields = $this->parser->parse($request->user(), $data['type'], $data['text']);
        } catch (ListingParseException $e) {
            return response()->json(['message' => $e->getMessage(), 'reason' => $e->reason], $e->httpStatus);
        }

        return response()->json(['data' => ['type' => $data['type'], 'fields' => (object) $fields]]);
    }
}
