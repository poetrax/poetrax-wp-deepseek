<?php
/**
 * POST /api/payments/credits - покупка 💎 за реальные деньги
 */
public function purchaseCredits(): string
{
    $userId = $this->getCurrentUserId();
    $packageId = $this->getParam('package_id'); // 100, 500, 1000 💎
    
    $packages = [
        '100' => ['credits' => 100, 'price' => 99],
        '500' => ['credits' => 500, 'price' => 399],
        '1000' => ['credits' => 1000, 'price' => 699],
    ];
    
    $package = $packages[$packageId] ?? null;
    if (!$package) {
        return $this->jsonError('Invalid package', 400);
    }
    
    // Создаём платёж в ЮKassa
    $payment = $this->yooKassa->createPayment([
        'amount' => $package['price'],
        'description' => "Покупка {$package['credits']} 💎",
        'metadata' => [
            'user_id' => $userId,
            'credits' => $package['credits']
        ]
    ]);
    
    return $this->jsonSuccess([
        'payment_id' => $payment->getId(),
        'confirmation_url' => $payment->getConfirmation()->getConfirmationUrl()
    ]);
}

/**
 * POST /api/subscribe - оформить подписку
 */
public function subscribe(): string
{
    $userId = $this->getCurrentUserId();
    $plan = $this->getParam('plan'); // monthly, yearly
    
    $plans = [
        'monthly' => ['price' => 299, 'days' => 30],
        'yearly' => ['price' => 2990, 'days' => 365],
    ];
    
    $planData = $plans[$plan] ?? null;
    if (!$planData) {
        return $this->jsonError('Invalid plan', 400);
    }
    
    // Платеж через ЮKassa
    $payment = $this->yooKassa->createPayment([
        'amount' => $planData['price'],
        'description' => "Подписка {$plan}",
        'metadata' => [
            'user_id' => $userId,
            'plan' => $plan,
            'type' => 'subscription'
        ]
    ]);
    
    return $this->jsonSuccess([
        'payment_id' => $payment->getId(),
        'confirmation_url' => $payment->getConfirmation()->getConfirmationUrl()
    ]);
}

/**
 * Webhook для ЮKassa
 */
public function yookassaWebhook(): string
{
    $payload = json_decode(file_get_contents('php://input'), true);
    
    if ($payload['event'] === 'payment.succeeded') {
        $metadata = $payload['object']['metadata'];
        
        if ($metadata['type'] === 'subscription') {
            // Активируем подписку
            $this->activateSubscription(
                $metadata['user_id'],
                $metadata['plan'],
                $payload['object']['id']
            );
        } else {
            // Начисляем 💎
            $balanceRepo = new BalanceRepository();
            $balanceRepo->addCredits(
                $metadata['user_id'],
                $metadata['credits'],
                'Покупка через ЮKassa'
            );
        }
    }
    
    return $this->jsonSuccess(['ok' => true]);
}