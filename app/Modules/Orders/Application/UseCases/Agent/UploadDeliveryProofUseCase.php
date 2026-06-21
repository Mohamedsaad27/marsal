<?php

namespace App\Modules\Orders\Application\UseCases\Agent;

use App\Modules\Orders\Application\DTOs\UploadOrderProofDTO;
use App\Modules\Orders\Application\Exceptions\OrderNotFoundException;
use App\Modules\Orders\Domain\Interfaces\AgentOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadDeliveryProofUseCase
{
    private const MAX_SIZE_MB = 5;

    public function __construct(
        private AgentOrderRepositoryInterface $orders,
    ) {}

    public function execute(UploadOrderProofDTO $dto): array
    {
        $order = $this->orders->findForAgent($dto->orderId, $dto->deliveryAgentId);

        if ($order === null) {
            throw new OrderNotFoundException();
        }

        $directory = "proofs/{$dto->deliveryAgentId}/{$dto->orderId}";
        $filename = Str::uuid() . '.' . $dto->photo->getClientOriginalExtension();
        $path = $dto->photo->storeAs($directory, $filename, 'public');
        $fileUrl = Storage::disk('public')->url($path);
        $proofId = (string) Str::uuid();
        $now = now();

        DB::table('order_proofs')->insert([
            'order_proof_id' => $proofId,
            'order_id' => $dto->orderId,
            'uploaded_by' => $dto->userId,
            'file_type' => $dto->fileType->value,
            'file_url' => $fileUrl,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'proof_id' => $proofId,
            'file_url' => $fileUrl,
            'file_type' => $dto->fileType->value,
        ];
    }

    public static function maxSizeKb(): int
    {
        return self::MAX_SIZE_MB * 1024;
    }
}
