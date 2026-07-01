<?php

declare(strict_types=1);

namespace App\Http\Controllers\Payments;

use App\Actions\Payments\HandleStripeWebhookAction;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UnexpectedValueException;

final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, HandleStripeWebhookAction $action): JsonResponse
    {
        /** @var string $payload */
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature') ?? '';

        $statusCode = 200;
        $responseData = [];

        try {
            $status = $action($payload, $sigHeader);
            $responseData = ['status' => $status];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $responseData = ['error' => 'Invalid signature: '.$e->getMessage()];
            $statusCode = 400;
        } catch (UnexpectedValueException $e) {
            $responseData = ['error' => 'Invalid payload: '.$e->getMessage()];
            $statusCode = 400;
        } catch (Exception $e) {
            $responseData = ['error' => 'Webhook processing failed: '.$e->getMessage()];
            $statusCode = 500;
        }

        return response()->json($responseData, $statusCode);
    }
}
