<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhooks;

use App\Actions\Webhooks\CreateWebhookAction;
use App\Actions\Webhooks\DisableWebhookAction;
use App\Actions\Webhooks\RotateWebhookSecretAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Webhooks\StoreWebhookRequest;
use App\Http\Resources\Webhooks\WebhookResource;
use App\Http\Resources\Webhooks\WebhookSecretDisclosureResource;
use App\Models\Organizer;
use App\Models\Webhook;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

final class WebhookController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CreateWebhookAction $createWebhook,
        private readonly DisableWebhookAction $disableWebhook,
        private readonly RotateWebhookSecretAction $rotateWebhookSecret,
    ) {}

    public function index(Organizer $organizer): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Webhook::class, $organizer]);

        return WebhookResource::collection($organizer->webhooks()->latest('webhook_id')->get());
    }

    public function store(StoreWebhookRequest $request, Organizer $organizer): JsonResponse
    {
        $this->authorize('create', [Webhook::class, $organizer]);

        return new WebhookSecretDisclosureResource(($this->createWebhook)($organizer, $request->toDto()))->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroy(Organizer $organizer, int $webhook): Response
    {
        $this->authorize('viewAny', [Webhook::class, $organizer]);

        ($this->disableWebhook)($organizer, $webhook);

        return response()->noContent();
    }

    public function rotate(Organizer $organizer, int $webhook): JsonResponse
    {
        $this->authorize('viewAny', [Webhook::class, $organizer]);

        return new WebhookSecretDisclosureResource(($this->rotateWebhookSecret)($organizer, $webhook))->response();
    }
}
