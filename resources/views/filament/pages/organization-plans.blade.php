<x-filament::page>
    <style>
        .org-card {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;

            margin-bottom: 20px;
        }
        .org-card .org-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #f59e0b;
        }
        .org-card .org-name {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        .org-card .org-label {
            font-size: 13px;
            color: #6b7280;
        }
        .org-card .org-plan {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }

        .plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }
        .plan-card {
            position: relative;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 22px;
            background: #ffffff;
            color: #111827;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .plan-card.plan-card-active {
            border-color: #f59e0b;
            box-shadow: 0 10px 24px rgba(245, 158, 11, 0.2);
        }
        .plan-badge {
            position: absolute;
            right: 18px;
            top: 18px;
            background: #f59e0b;
            color: #111827;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
        }
        .plan-tier {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #f59e0b;
        }
        .plan-price {
            margin-top: 10px;
            font-size: 22px;
            font-weight: 700;
            color: #111827;
        }
        .plan-interval {
            font-size: 13px;
            font-weight: 400;
            color: #6b7280;
        }
        .plan-limit {
            margin-top: 12px;
            font-size: 13px;
            color: #6b7280;
        }
        .plan-action {
            margin-top: 18px;
            display: inline-flex;
            width: 100%;
            justify-content: center;
            border-radius: 999px;
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 700;
        }
        .plan-action.current {
            background: #f59e0b;
            color: #111827;
        }
        .plan-action.upgrade {
            background: #fef3c7;
            color: #b45309;
        }

        .dark .org-card {
            border-color: #e4e4e412;
            background:transparent;
        }
        .dark .org-card .org-name,
        .dark .org-card .org-plan {
            color: #f8fafc;
        }
        .dark .org-card .org-label {
            color: #94a3b8;
        }

        .dark .plan-card {
            border-color: #e4e4e412;
       background:transparent;
            color: #e2e8f0;
         
        }
        .dark .plan-card.plan-card-active {
            border-color: rgba(245, 158, 11, 0.7);
            box-shadow: 0 12px 26px rgba(245, 158, 11, 0.15);
        }
        .dark .plan-price {
            color: #f8fafc;
        }
        .dark .plan-interval,
        .dark .plan-limit {
            color: #9ca3af;
        }
        .dark .plan-action.upgrade {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }
    </style>

    <div class="space-y-6">
        @if (! $organization)
            <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-neutral-900 dark:text-gray-300">
                No organization context found for this account.
            </div>
        @else
            <div class="org-card">
                <p class="org-title">Organization</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="org-name">{{ $organization->name }}</span>
                    <span class="org-label">Active plan:</span>
                    <span class="org-plan">
                        {{ optional($organization->activeSubscription()?->plan)->name ?? 'None' }}
                    </span>
                </div>
            </div>

            @if ($plans->isEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-600 shadow-sm dark:border-gray-800 dark:bg-neutral-900 dark:text-gray-300">
                    No active system plans are available right now.
                </div>
            @else
                <div class="plan-grid">
                    @foreach ($plans as $plan)
                        @php
                            $isActive = $activePlanId === $plan->id;
                            $price = '$' . number_format($plan->price_cents / 100, 2);
                            $interval = $plan->billing_interval === 'year' ? 'year' : 'month';
                            $maxCourts = $plan->features_config['max_courts'] ?? null;
                            $maxCourtsLabel = $maxCourts === null ? 'Unlimited courts' : $maxCourts . ' courts';
                        @endphp
                        <div class="plan-card {{ $isActive ? 'plan-card-active' : '' }}">
                            @if ($isActive)
                                <span class="plan-badge">
                                    Active
                                </span>
                            @endif
                            <p class="plan-tier">
                                {{ strtoupper($plan->slug) }}
                            </p>
                            <p class="plan-price">
                                {{ $price }}
                                <span class="plan-interval">/ {{ $interval }}</span>
                            </p>
                            <p class="plan-limit">
                                {{ $maxCourtsLabel }}
                            </p>
                            <div class="mt-6">
                                @if ($isActive)
                                    <span class="plan-action current">
                                        Current Plan
                                    </span>
                                @else
                                    <span class="plan-action upgrade">
                                        Upgrade
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-filament::page>
