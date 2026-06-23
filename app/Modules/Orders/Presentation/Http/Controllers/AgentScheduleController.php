<?php

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Infrastructure\Traits\ApiResponseTrait;
use App\Modules\Orders\Application\UseCases\Agent\GetScheduleCalendarUseCase;
use App\Modules\Orders\Application\UseCases\Agent\ListPostponedOrdersUseCase;
use App\Modules\Orders\Presentation\Http\Requests\GetScheduleCalendarRequest;
use App\Modules\Orders\Presentation\Http\Requests\ListAgentScheduleRequest;
use App\Modules\Orders\Presentation\Http\Resources\AgentPostponedOrderResource;
use App\Modules\Orders\Presentation\Http\Resources\AgentScheduleCalendarResource;
use Illuminate\Http\JsonResponse;

class AgentScheduleController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private ListPostponedOrdersUseCase $listPostponed,
        private GetScheduleCalendarUseCase $getCalendar,
    ) {}

    public function index(ListAgentScheduleRequest $request): JsonResponse
    {
        $orders = $this->listPostponed->execute(
            userId: $request->user()->user_id,
            date: $request->query('date'),
            month: $request->query('month'),
        );

        return $this->success(
            [
                'items' => AgentPostponedOrderResource::collection($orders),
                'total' => $orders->count(),
            ],
            __('orders::messages.schedule_list_success'),
        );
    }

    public function calendar(GetScheduleCalendarRequest $request): JsonResponse
    {
        $data = $this->getCalendar->execute(
            userId: $request->user()->user_id,
            month: $request->query('month'),
        );

        return $this->success(
            new AgentScheduleCalendarResource($data),
            __('orders::messages.schedule_calendar_success'),
        );
    }
}
