# Revised Implementation Plan: فصل العمولات والتسويات

> هذه وثيقة تخطيط فقط. لا تتضمن تنفيذ كود أو migrations. بُنيت بعد مراجعة التدفق الحالي في المشروع، بما فيه إنشاء التحصيل، `mark-cash-received`، إنشاء ودفع التسويات، الأرصدة، التقارير، والـAPI Resources.

## 1. الخلاصة التنفيذية

المطلوب ليس مجرد إضافة عمولة ثانية. التغيير يعيد تعريف دفتر العلاقة المالية بين ثلاثة أطراف:

```text
Customer -> Delivery Agent -> System -> Shipping Company
```

القرارات المعتمدة في هذه الخطة:

1. عمولة المندوب وعمولة النظام مستقلتان تمامًا.
2. `delivery_agents.balance` يصبح Signed Net Financial Position، وليس Cash in Hand.
3. `shipping_companies.balance` يصبح Signed Net Financial Position بين النظام والشركة.
4. القيم السالبة في `agent_net_due` و`company_net_due` والأرصدة صحيحة ومقصودة، ولا تُحوّل إلى صفر ولا تُرفض لمجرد الإشارة.
5. إنشاء Collection هو الحدث الذي ينشئ الالتزام ويزيد الرصيد بالقيمة الموقعة.
6. `mark-cash-received` إثبات تشغيلي لاستلام النقد فقط، ولا يعدّل أي Balance.
7. `Agent Settlement Paid` هو الحدث الوحيد الذي يزيل التزام المندوب من رصيده.
8. `Company Settlement Paid` هو الحدث الوحيد الذي يزيل التزام الشركة من رصيدها.
9. Agent Settlement وCompany Settlement ثنائيتا الاتجاه وتحافظان على `net_amount` الموقعة.
10. نفس Collection تمر بصورة مستقلة في Agent Settlement وCompany Settlement.
11. قيم العمولتين تُحفظ Snapshot داخل Collection عند إنشائها ولا يعاد احتسابها من الإعدادات لاحقًا.
12. التصميم المعتمد نهائيًا هو Option B باستخدام `settlement_items` وربط البنود عند إنشاء Draft، لا إعادة اختيارها عند الدفع.
13. تغيير الـAPI سيكون Breaking Rename في الإصدار نفسه، بلا compatibility layer أو aliases أو فترة deprecation.

النتيجة في المثال الأساسي:

```text
C  = 1000
Ca = 50
Cs = 80

agent_net_due   = 950
company_net_due = 920
system_margin   = 30
```

شركة الشحن تستحق `920`، ولا تخصم منها عمولة المندوب مرة ثانية.

---

## 2. Financial Definitions and Invariants

### 2.1 القيم الأساسية

| المصطلح | التعريف المالي | الإشارة |
|---|---|---|
| `collected_amount` أو `C` | إجمالي ما حصله المندوب من العميل لهذا الطلب | غير سالب |
| `agent_commission_amount` أو `Ca` | عمولة المندوب المحفوظة وقت إنشاء Collection | غير سالبة |
| `agent_net_due` | `C - Ca` | موجبة أو صفر أو سالبة |
| `system_commission_amount` أو `Cs` | عمولة النظام على شركة الشحن، محفوظة وقت إنشاء Collection | غير سالبة |
| `company_net_due` | `C - Cs` | موجبة أو صفر أو سالبة |
| `system_margin` | `Cs - Ca` | موجبة أو صفر أو سالبة |
| `delivery_agents.balance` | مجموع الالتزامات غير المسوّاة بين المندوب والنظام | Signed |
| `shipping_companies.balance` | Signed Net Financial Position غير المسوّى بين النظام والشركة | Signed |
| `cash_received_at` | دليل أن الإدارة استلمت النقد المطلوب من المندوب | لا ينشئ قيدًا ماليًا ولا يزيله |
| Agent Settlement | تجميع وتسوية العلاقة المالية بين المندوب والنظام | ثنائي الاتجاه |
| Company Settlement | تجميع وتسوية العلاقة المالية بين النظام والشركة | ثنائي الاتجاه |
| `order_financials.is_settled` | اكتمال التسويتين المدفوعتين لنفس الطلب | Boolean |

### 2.2 معنى الإشارات المالية

#### Agent Position

```text
agent_net_due > 0
Agent owes System

agent_net_due = 0
No net payment between Agent and System

agent_net_due < 0
System owes Agent
```

وينطبق المعنى نفسه على الرصيد المجمع:

```text
delivery_agents.balance > 0
Agent owes System

delivery_agents.balance = 0
No unsettled net position

delivery_agents.balance < 0
System owes Agent
```

#### Company Position

```text
company_net_due > 0
System owes Shipping Company

company_net_due = 0
No net payment between System and Shipping Company

company_net_due < 0
Shipping Company owes System
```

وينطبق المعنى نفسه على الرصيد المجمع:

```text
shipping_companies.balance > 0
System owes Shipping Company

shipping_companies.balance = 0
No unsettled net position

shipping_companies.balance < 0
Shipping Company owes System
```

### 2.3 معادلات لا يجوز تغييرها

```text
agent_commission_amount  = snapshot(delivery_agents.commission_value)
system_commission_amount = snapshot(shipping_companies.commission_value)

agent_net_due   = collected_amount - agent_commission_amount
company_net_due = collected_amount - system_commission_amount
system_margin   = system_commission_amount - agent_commission_amount
```

لا يستخدم `max(0, ...)` في حساب `agent_net_due` أو `company_net_due`، ولا يُرفض أي منهما لمجرد أن الناتج سالب.

لا يلزم تخزين `system_margin` في عمود مستقل؛ هو قيمة مشتقة دائمًا من Snapshot العمولتين:

```text
system_margin = system_commission_amount - agent_commission_amount
```

### 2.4 معنى أعمدة Settlement

| النوع | `total_collections` | `total_commissions` | `net_amount` |
|---|---|---|---|
| Agent | `SUM(collected_amount)` | `SUM(agent_commission_amount)` | `SUM(agent_net_due)` |
| Company | `SUM(collected_amount)` | `SUM(system_commission_amount)` | `SUM(company_net_due)` |

يجب أن يبقى `settlements.net_amount` Signed في نوعي التسوية بلا استخدام `abs()` في التخزين أو الحساب المحاسبي. تستخدم القيمة المطلقة للعرض فقط في `payable_amount`.

### 2.5 ثوابت السلامة

1. كل أثر على Balance له Event مالي واحد فقط.
2. كل تعديل أو عكس قبل التسوية يستخدم فرق القيم المحفوظة، لا إعدادات العمولة الحالية.
3. كل Settlement أو Report يقرأ Snapshot من Collection أو Settlement Item، لا `commission_value` الحالية.
4. Collection واحدة يمكن أن تكون في Agent Settlement واحدة وCompany Settlement واحدة بصورة مستقلة.
5. `is_settled=true` يتطلب Agent Settlement مدفوعة وCompany Settlement مدفوعة.
6. Draft Settlement لا تتغير بنودها أو مجاميعها بصمت بعد إنشائها.
7. الإشارة في Agent وCompany تحدد من هو المدين، ولا تُفقد في التخزين أو التجميع أو التقارير.

---

## 3. دورة الأحداث ومن يعدّل كل Balance

### 3.1 جدول القيود

| الحدث | `delivery_agents.balance` | `shipping_companies.balance` | السبب |
|---|---:|---:|---|
| إنشاء Collection | `+= agent_net_due` | `+= company_net_due` | نشوء الالتزامين |
| تعديل Collection مفتوحة | `+= new_agent_net_due - old_agent_net_due` | `+= new_company_net_due - old_company_net_due` | تسجيل فرق الالتزام فقط |
| عكس Collection قبل ارتباطها بتسوية | `-= stored_agent_net_due` | `-= stored_company_net_due` | إلغاء الأثر الأصلي بالقيم المحفوظة |
| `mark-cash-received` | لا تغيير | لا تغيير | إثبات حركة نقدية تشغيلية، وليس قيد تسوية |
| Agent Settlement Paid | `-= settlement.net_amount` | لا تغيير | إزالة صافي التزام المندوب الموقّع |
| Company Settlement Paid | لا تغيير | `-= settlement.net_amount` | إزالة المركز المالي الموقّع للشركة |

استخدام الطرح مع قيمة Agent Settlement سالبة مقصود:

```text
balance before settlement = -20
settlement.net_amount      = -20
balance after settlement  = -20 - (-20) = 0
```

وينطبق الحساب نفسه على Company Settlement السالبة:

```text
company balance before settlement = -20
settlement.net_amount              = -20
company balance after settlement  = -20 - (-20) = 0
```

### 3.2 لماذا لا يعدّل `mark-cash-received` الرصيد

التدفق الحالي في [`AdminCollectionRepository::markCashReceived()`](../app/Modules/Collections/Infrastructure/Persistence/Repositories/AdminCollectionRepository.php) يكتب فقط:

```text
cash_received_at
cash_received_by
```

ولا يسجل مبلغ تسليم فعليًا، ولا يوزع المبلغ على عمولة أو مديونية سالبة، ولا ينشئ Payment Record. لذلك استخدامه لخفض Signed Net Position ثم خفض الرصيد مرة أخرى عند `markPaid()` سيؤدي إلى Double Deduction.

التعريف المعتمد:

| المرحلة | دورها |
|---|---|
| Collection | تنشئ الالتزام المالي بالقيم الموقعة |
| Cash Handover | تثبت تشغيليًا أن النقد المطلوب تم استلامه، وتفتح أهلية البنود الموجبة للتسوية |
| Agent Settlement | تجمع الالتزامات، تحدد اتجاه الدفع، ثم تصفّر صافي المركز المالي عند Paid |

هذا يعني أن `balance` هو **unsettled ledger position**. قد يبقى موجبًا بين إثبات استلام النقد وبين اعتماد التسوية النهائية، لأن إثبات النقد ليس قيد التسوية المحاسبي في نموذج البيانات الحالي.

في Collection موجبة، المبلغ المتوقع تسليمه تشغيليًا هو `agent_net_due` وفق نموذج احتفاظ المندوب بعمولته. أما عندما يكون `agent_net_due <= 0` فلا يوجد Cash Handover من المندوب؛ يجب أن يرفض `MarkCashReceivedUseCase` الطلب برسالة Business واضحة بدل تسجيل إثبات نقد غير موجود.

إذا أراد العمل أن يكون Cash Handover نفسه هو قيد التسوية، فلا يكفي الحقلان الحاليان. سيلزم تسجيل `cash_handover_amount` وحركات دفع عمولة منفصلة، وإعادة تعريف Agent Settlement كمطابقة بلا أثر مالي. هذا تصميم بديل أكبر وغير معتمد في هذه الخطة.

### 3.3 المثال الطبيعي

```text
C=1000, Ca=50, Cs=80

Collection created:
agent.balance   += 950
company.balance += 920

mark-cash-received:
no balance changes

Agent Settlement Paid, net=950:
agent.balance -= 950

Company Settlement Paid, net=920:
company.balance -= 920
```

### 3.4 مثال المندوب السالب

```text
C=30, Ca=50
agent_net_due=-20

Collection created:
agent.balance += -20

No cash handover is required for Agent Settlement eligibility.

Agent Settlement Paid, net=-20:
system pays agent 20
agent.balance -= -20
agent.balance becomes 0
```

### 3.5 مثال الشركة السالب

```text
C=30, Cs=50
company_net_due=-20

Collection created:
company.balance += -20

No cash receipt prerequisite is required for Company Settlement eligibility.

Company Settlement Paid, net=-20:
shipping company pays system 20
company.balance -= -20
company.balance becomes 0
```

---

## 4. نتائج مراجعة المشروع الحالي

المراجعة أكدت النقاط التالية:

1. [`OrderStatusChangeService::recordCollection()`](../app/Modules/Orders/Domain/Services/OrderStatusChangeService.php) و[`ReviewApprovalRequestUseCase::applyApprovedFinancialEffect()`](../app/Modules/Orders/Application/UseCases/Admin/ReviewApprovalRequestUseCase.php) ينفذان منطق إنشاء Collection مرتين بصورة مستقلة.
2. المساران يزيدان رصيد المندوب بـ`collected_amount` لا بـ`agent_net_due`.
3. المسار العادي يحدّث `order_financials.collected_amount` فقط، بينما مسار الموافقة يكتب العمولة والصافي أيضًا.
4. `mark-cash-received` لا يعدّل الرصيد حاليًا، وهو السلوك الذي يجب الحفاظ عليه.
5. [`SettlementRepository`](../app/Modules/Collections/Infrastructure/Persistence/Repositories/SettlementRepository.php) يشترط `cash_received_at IS NOT NULL` لكلا نوعي التسوية، ويعيد Query للبنود عند الدفع.
6. `SettlementRepository::markPaid()` يضع `is_settled=true` بعد أول تسوية ويخصم `net_amount` من الرصيد.
7. `collections.settlement_id` الواحد يمنع مرور Collection نفسها في التسويتين.
8. `shipping_companies.balance` يُخصم عند الدفع ولا توجد زيادة مقابلة عند إنشاء Collection.
9. [`DeliveryAgent::getDeliveryAgentActualBalance()`](../app/Modules/Users/Infrastructure/Database/Models/DeliveryAgent.php) يطرح عمولة طلب واحد من رصيد مجمع، وهو غير صحيح.
10. [`UserRepository`](../app/Modules/Users/Infrastructure/Persistence/UserRepository.php) يعتبر الرصيد غير الصفري فقط عندما يكون `balance > 0`، ولذلك يتجاهل مديونية النظام للمندوب عندما يكون الرصيد سالبًا.
11. محفظة الشركة وتقارير التحصيلات تقرأ عمولة وصافي المندوب الحاليين.
12. `SettlementRepository::stats()` يجمع قيم `net_amount` بلا فصل النوع أو الاتجاه؛ القيم السالبة قد تلغي الموجبة وتنتج Summary مضللة.
13. لا توجد اختبارات حالية لوحدة Collections/Settlements؛ الاختبارات الموجودة تغطي جزءًا من مساري إنشاء Collection فقط.

---

## 5. Snapshot العمولات وتوحيد إنشاء الأثر المالي

### 5.1 Snapshot على مستويين

يوجد نوعان مختلفان من Snapshot ويجب عدم الخلط بينهما:

| المستوى | وقت التثبيت | ما الذي يمنعه |
|---|---|---|
| Collection Snapshot | وقت إنشاء Collection | تغير العمولات القديمة بعد تعديل إعدادات Agent أو Company |
| Settlement Item Snapshot | وقت إنشاء Draft Settlement | تغير بنود أو مجاميع Draft بين الإنشاء والدفع |

### 5.2 خدمة كتابة مالية واحدة

يُستخرج منطق الإنشاء والتعديل إلى خدمة واحدة، مثل:

```text
RecordCollectionService
```

ويستدعيها المساران:

```text
OrderStatusChangeService::recordCollection()
ReviewApprovalRequestUseCase::applyApprovedFinancialEffect()
```

مسؤوليات الخدمة:

1. قفل Order وCollection المفتوحة عند الحاجة.
2. عند الإنشاء فقط، قراءة `delivery_agents.commission_value` و`shipping_companies.commission_value` مرة واحدة.
3. حساب وتخزين الأعمدة المالية الأربعة.
4. تحديث `order_financials` بنفس القيم في المسارين.
5. تعديل رصيد المندوب بفرق `agent_net_due`.
6. تعديل رصيد الشركة بفرق `company_net_due`.
7. إبقاء Snapshot القديم عند تعديل Collection موجودة، بدل إعادة قراءة إعدادات العمولة الحالية.
8. منع تعديل Collection بعد ربطها بأي Draft أو Paid Settlement؛ التصحيح بعد ذلك يحتاج Reversal صريحًا، لا تعديل الصف الأصلي.

### 5.3 توسيع الحاسبة

تُعدّل [`CommissionCalculatorService`](../app/Modules/Orders/Domain/Services/CommissionCalculatorService.php) لتنتج:

```php
[
    'agent_commission_amount' => $agentCommissionAmount,
    'agent_net_due' => $collectedAmount - $agentCommissionAmount,
    'system_commission_amount' => $systemCommissionAmount,
    'company_net_due' => $collectedAmount - $systemCommissionAmount,
]
```

لا يوجد Clamp لصافي المندوب أو صافي الشركة. الحسابات تُطبّع إلى منزلتين عشريتين، ولا تُقبل قيم مالية محسوبة من العميل.

---

## 6. قرار تصميم التسوية

### 6.1 Option A: غير معتمد

```text
collections.agent_settlement_id
collections.company_settlement_id
+ reconciliation check at markPaid()
```

آلية العمل:

1. Draft تحفظ المجاميع والفترة فقط.
2. البنود لا ترتبط فعليًا بالمسودة.
3. عند `markPaid()` يعاد Query للبنود حسب الفترة والجهة.
4. يجب مقارنة العدد والإجمالي والعمولة والصافي بالقيم المحفوظة قبل الدفع.
5. عند نجاح الدفع يُكتب FK الخاص بنوع التسوية.

| محور | التقييم |
|---|---|
| حجم التعديل | أقل |
| سلامة البيانات | مقبولة فقط مع قفل ومطابقة صارمة |
| Audit | ضعيف؛ لا توجد قائمة بنود ثابتة للمسودة |
| Concurrency | مسودتان قد تختاران البنود نفسها قبل الدفع |
| Draft stability | غير حقيقي؛ الحاجز يكتشف التغير لكنه لا يمنعه |
| Reversal لاحقًا | أصعب لأن تفاصيل سطر التسوية غير محفوظة |
| API details | البنود المعروضة قبل الدفع تظل Dynamic |

مخاطر متبقية حتى بعد Reconciliation:

1. يمكن إنشاء أكثر من Draft لنفس البنود.
2. أي Collection جديدة داخل الفترة تغير أهلية المسودة.
3. فشل الدفع بسبب تغير البنود يصبح سلوكًا متوقعًا يحتاج معالجة تشغيلية.
4. لا يمكن إثبات محتوى المسودة وقت اعتمادها إلا من المجاميع.

**القرار:** يُرفض هذا الخيار للتنفيذ، ولا تُضاف أعمدة `agent_settlement_id` أو `company_settlement_id` إلى `collections`.

### 6.2 Option B: التصميم المعتمد

```text
settlement_items
```

عند إنشاء Draft، تُحجز Collections وتُحفظ البنود التالية:

```text
settlement_item_id
settlement_id
collection_id
settlement_type
gross_amount
commission_amount
net_amount
created_at
updated_at
```

التعريف المقترح للحقول:

| الحقل | النوع |
|---|---|
| `settlement_item_id` | UUID PK |
| `settlement_id` | UUID FK مسمى إلى `settlements` |
| `collection_id` | UUID FK مسمى إلى `collections` |
| `settlement_type` | `TINYINT UNSIGNED` مع Comment خريطة الـEnum |
| `gross_amount` | `DECIMAL(12,2)` |
| `commission_amount` | `DECIMAL(12,2)` |
| `net_amount` | `DECIMAL(12,2)` Signed |

القيود المطلوبة:

```text
UNIQUE(collection_id, settlement_type)
INDEX(settlement_id)
INDEX(collection_id, settlement_type)
```

آلية العمل:

1. اختيار البنود وإنشاء Settlement وSettlement Items يتم في Transaction واحدة.
2. Collections المؤهلة تُقفل بـ`lockForUpdate()` قبل إدخال البنود.
3. القيد الفريد يمنع ربط Collection بتسويتين من النوع نفسه تحت السباق.
4. نفس Collection تقبل Item من نوع Agent وItem من نوع Company.
5. المجاميع تُحسب من Items المحفوظة.
6. `approve()` و`markPaid()` يقرآن Items نفسها ولا يعيدان اختيار Collections.
7. تفاصيل التسوية تعرض Snapshot الموجودة في Items، لا القيم الحية في Collection.

| محور | التقييم |
|---|---|
| حجم التعديل | أكبر قليلًا |
| سلامة البيانات | عالية؛ البنود والمجاميع ثابتة |
| Audit | واضح على مستوى كل Collection |
| Concurrency | محمي بالـtransaction والقفل والقيد الفريد |
| Draft stability | حقيقي منذ لحظة الإنشاء |
| Reversal لاحقًا | أسهل عبر Item أو Settlement تعويضي |
| API details | ثابتة ومتطابقة مع ما تم اعتماده ودفعه |

### 6.3 القرار النهائي

**Option B باستخدام `settlement_items` قرار نهائي وملزم للتنفيذ.**

السبب أن المشروع ما زال يسمح بـ`migrate:fresh --seed`، ولا توجد حاجة لحمل دين تصميم معروف إلى الإنتاج. فرق التنفيذ محدود مقارنة بتحسن Audit وConcurrency وثبات المسودة.

في التصميم المعتمد لا توجد أعمدة `agent_settlement_id` أو `company_settlement_id` داخل `collections`. تُستنتج حالة كل جهة من `settlement_items.settlement_type` والتسوية المرتبطة بها.

لا تُحذف Draft مالية بعد إنشاء Items في النطاق الحالي، لأن القيد الفريد يجعل البند جزءًا دائمًا من سجل التدقيق. إضافة Cancel/Reversal لاحقًا يجب أن تكون بحالة صريحة وسجل تعويضي، لا Soft Delete صامتًا.

---

## 7. تغييرات قاعدة البيانات المقترحة

### 7.1 `collections`

تعديل [هجرة الإنشاء](../database/migrations/2026_05_19_144502_create_collections_table.php):

| الحالي | المقترح |
|---|---|
| `commission_amount` | `agent_commission_amount` |
| `net_due` | `agent_net_due` |
| لا يوجد | `system_commission_amount` |
| لا يوجد | `company_net_due` |
| `settlement_id` | يُحذف نهائيًا؛ الربط عبر `settlement_items` |

الفهارس المطلوبة تشمل الجهة والتاريخ وحقول إثبات النقد، بينما أهلية التسوية تُفحص عبر `NOT EXISTS` على `settlement_items` لنوع التسوية المطلوب.

يجب تحديث [`2026_06_25_190000_add_cash_received_fields_to_collections_table.php`](../database/migrations/2026_06_25_190000_add_cash_received_fields_to_collections_table.php)، لأن `cash_received_at` يستخدم حاليًا `after('settlement_id')`.

### 7.2 `settlement_items`

إضافة هجرة إنشاء وجدول Model جديدين داخل Collections Module أو مسار migrations المعتمد في المشروع.

حقول المبالغ الجديدة والمعدلة تستخدم `DECIMAL(12,2)` وفق معيار المشروع، وتُحفظ القيم الموقعة في `net_amount`. تُسمّى جميع قيود FK والفهارس صراحةً.

العلاقات:

```text
Settlement hasMany SettlementItem
SettlementItem belongsTo Settlement
SettlementItem belongsTo Collection
Collection hasMany SettlementItem
```

### 7.3 `order_financials`

تعديل [هجرة الإنشاء](../database/migrations/2026_05_19_142747_create_order_financials_table.php):

| الحالي | المقترح |
|---|---|
| `commission_amount` | `agent_commission_amount` |
| لا يوجد | `system_commission_amount` |
| `net_due_company` | يبقى الاسم، ويصبح `collected_amount - system_commission_amount` |
| `is_settled` | يبقى، مع تحديث التعليق إلى اكتمال التسويتين المدفوعتين |

يجب تحديث نسختي Model:

```text
app/Modules/Orders/Infrastructure/Database/Models/OrderFinancial.php
app/Modules/Dashboard/Infrastructure/Database/Models/OrderFinancial.php
```

### 7.4 تعريف الرصيدين في migrations

يُحدّث تعليق `delivery_agents.balance` إلى:

```text
Signed unsettled net position: positive=agent owes system, negative=system owes agent
```

ويُحدّث تعريف `shipping_companies.balance` إلى:

```text
Signed unsettled net position: positive=system owes company, negative=company owes system
```

### 7.5 `settlements`

لا يلزم تغيير أسماء أعمدة المجاميع. يُحدّث تعليق `net_amount` لتوضيح أن معناه حسب `settlement_type` وأنه Signed في Agent وCompany Settlements.

---

## 8. إنشاء وتعديل وعكس Collection

### 8.1 إنشاء جديد

داخل Transaction واحدة:

1. قفل Order والجهتين عند تعديل الأرصدة.
2. قراءة قيمتي العمولة الحالية مرة واحدة.
3. حساب الأعمدة الأربعة بلا Clamp للمندوب أو الشركة.
4. إنشاء Collection بالـsnapshots.
5. كتابة القيم المناظرة في `order_financials`.
6. `agent.balance += agent_net_due`.
7. `company.balance += company_net_due`.

### 8.2 تعديل Collection موجودة قبل أي Draft

لا تُقرأ إعدادات العمولة مرة أخرى. تستخدم العمولة المحفوظة:

```text
new_agent_net_due   = new_C - stored_agent_commission_amount
new_company_net_due = new_C - stored_system_commission_amount
```

ثم:

```text
agent.balance   += new_agent_net_due - old_agent_net_due
company.balance += new_company_net_due - old_company_net_due
```

### 8.3 العكس قبل التسوية

المسار الحالي [`reverseOpenCollection()`](../app/Modules/Orders/Domain/Services/OrderStatusChangeService.php) يجب أن يستخدم القيم المحفوظة:

```text
agent.balance   -= stored_agent_net_due
company.balance -= stored_company_net_due
```

ثم يصفر الحقول المالية الأربعة وحقول `order_financials` المرتبطة، ويزيل إثبات النقد إن كان العكس التشغيلي يسمح بذلك.

### 8.4 العكس بعد إنشاء Settlement Item

لا يُسمح بتعديل أو تصفير Collection الأصلية. يجب رمي Business Exception واضحة. Reversal بعد الارتباط يحتاج تدفقًا تعويضيًا مستقلًا يحفظ Audit Trail؛ هذا خارج التنفيذ الأساسي لكنه مدعوم بنيويًا بصورة أفضل مع `settlement_items`.

---

## 9. قواعد التسويات الجديدة

### 9.1 أهلية Agent Settlement

Collection مؤهلة عندما:

```text
delivery_agent_id matches
AND collected_at is within period
AND no Agent settlement_item exists
AND (
    agent_net_due <= 0
    OR cash_received_at IS NOT NULL
)
```

المعنى:

| `agent_net_due` | Cash Handover required before Draft? | السبب |
|---:|---|---|
| `> 0` | نعم | المندوب مدين للنظام |
| `= 0` | لا | لا يوجد صافي دفع |
| `< 0` | لا | النظام مدين للمندوب |

### 9.2 اتجاه Agent Settlement

```text
agent_settlement_net = SUM(agent_net_due)
```

| القيمة | الاتجاه | مبلغ الدفع المعروض |
|---:|---|---:|
| `> 0` | `agent_to_system` | `abs(net_amount)` |
| `< 0` | `system_to_agent` | `abs(net_amount)` |
| `= 0` | `no_payment` | `0` |

يُضاف إلى الـAPI دون تغيير القيمة المحاسبية المخزنة:

```json
{
  "net_amount": "-20.00",
  "payment_direction": "system_to_agent",
  "payable_amount": "20.00"
}
```

يصبح `payment_method` مطلوبًا عند وجود دفع فعلي، ويمكن أن يكون `null` عندما يكون `net_amount=0`. حالة Paid عند الصفر تعني Reconciled بلا تحويل نقدي.

### 9.3 أهلية Company Settlement

Collection مؤهلة عندما:

```text
shipping_company_id matches
AND collected_at is within period
AND no Company settlement_item exists
AND (
    company_net_due <= 0
    OR cash_received_at IS NOT NULL
)
```

المعنى:

| `company_net_due` | `cash_received_at` مطلوب قبل Draft؟ | السبب |
|---:|---|---|
| `> 0` | نعم | النظام سيدفع للشركة، فينتظر إثبات استلام النقد من المندوب |
| `= 0` | لا | لا يوجد صافي دفع |
| `< 0` | لا | الشركة مدينة للنظام، فلا يكون استلام النقد من المندوب شرطًا لهذا الالتزام |

شرط `cash_received_at` يظل بوابة تمويل للبنود التي سيدفع فيها النظام للشركة، وليس شرطًا عامًا على كل Company Settlement.

### 9.4 اتجاه Company Settlement

```text
company_settlement_net = SUM(company_net_due)
```

| القيمة | الاتجاه | مبلغ الدفع المعروض |
|---:|---|---:|
| `> 0` | `system_to_company` | `abs(net_amount)` |
| `< 0` | `company_to_system` | `abs(net_amount)` |
| `= 0` | `no_payment` | `0` |

تبقى `net_amount` Signed في قاعدة البيانات والتقارير. `payable_amount` فقط تستخدم `abs(net_amount)` للعرض.

### 9.5 إنشاء Draft

يُستبدل الفصل الحالي بين `findEligibleCollections()` و`createFromCollections()` بعملية ذرية واحدة:

1. بدء Transaction.
2. اختيار البنود المؤهلة مع `lockForUpdate()`.
3. إنشاء Settlement.
4. إنشاء Settlement Items بالـsnapshots المناسبة لنوع التسوية.
5. حساب مجاميع Settlement من Items.
6. Commit.

القيد الفريد هو خط الدفاع الأخير إذا حاول طلبان إنشاء Draft متزامنتين لنفس الجهة والبنود.

### 9.6 `approve()`

تتغير الحالة فقط. لا يعاد Query على Collections ولا تعاد المجاميع. تفاصيل الاعتماد تأتي من Items المجمدة.

### 9.7 `markPaid()`

داخل Transaction ومع قفل Settlement والجهة:

1. التأكد أن الحالة Approved.
2. تحميل Settlement Items المحفوظة.
3. التأكد أن Items موجودة وأن مجاميعها تطابق Settlement كحاجز فساد بيانات.
4. تعديل Balance واحدة فقط حسب نوع التسوية.
5. تسجيل طريقة الدفع والمرجع و`paid_at`.
6. تحديث `order_financials.is_settled` فقط للطلبات التي اكتملت تسويتاها المدفوعتان.

Agent:

```text
delivery_agent.balance -= settlement.net_amount
```

Company:

```text
shipping_company.balance -= settlement.net_amount
```

لا تستخدم دالة تفترض أن مبلغ الخصم موجب؛ يجب أن يكون التحديث الحسابي في Agent وCompany قادرًا على طرح signed decimal بوضوح.

### 9.8 `is_settled`

وجود Item في Draft لا يعني أن الجهة تمت تسويتها. الشرط الصحيح لكل Order:

```text
EXISTS agent settlement_item joined to PAID agent settlement
AND
EXISTS company settlement_item joined to PAID company settlement
```

بعد أول تسوية Paid تبقى القيمة `false`. بعد الثانية تصبح `true`.

---

## 10. الأرصدة وواجهات الحذف والملف الشخصي

### 10.1 `getDeliveryAgentActualBalance()`

بعد تغيير معنى `balance`، طرح العمولة مرة أخرى يصبح Double Deduction.

الاستخدام الحالي الوحيد للدالة هو [`AgentProfileResource`](../app/Modules/Users/Presentation/Http/Resources/AgentProfileResource.php). التوصية:

1. إزالة `getDeliveryAgentActualBalance()` لأنها أصبحت اسمًا مضللًا ودالة زائدة.
2. قراءة `$agent->balance` مباشرة في Resource.
3. الحفاظ على مفتاح API الحالي `agent.balance` لتجنب كسر العميل.
4. توثيق أن القيمة Signed وإضافة `balance_direction` و`balance_amount=abs(balance)` بصورة ثابتة في الاستجابة.

### 10.2 حواجز حذف المستخدم

يُعدّل `deliveryAgentHasNonZeroBalance()` من:

```text
balance > 0
```

إلى:

```text
balance <> 0
```

حتى لا يمكن حذف مندوب والنظام مدين له.

ويُعدّل `shippingCompanyHasNonZeroBalance()` بالطريقة نفسها من `balance > 0` إلى `balance <> 0`، حتى لا يمكن حذف شركة عندما تكون مدينة للنظام أو يكون النظام مدينًا لها.

فحوصات Collections غير المسواة في [`UserRepository`](../app/Modules/Users/Infrastructure/Persistence/UserRepository.php) يجب أن تنتقل من `collections.settlement_id` إلى `settlement_items` وحالة Settlement لكل نوع.

---

## 11. مواضع القراءة والـAPI والتقارير

### 11.1 Collections API

[`AdminCollectionResource`](../app/Modules/Collections/Presentation/Http/Resources/Admin/AdminCollectionResource.php) يعرض صراحة:

```text
agent_commission_amount
agent_net_due
system_commission_amount
company_net_due
agent_settlement_status
company_settlement_status
```

لا يبقى مفتاح عام مبهم مثل `commission_amount` أو `net_due` في واجهة الأدمن.

[`AgentCollectionListResource`](../app/Modules/Collections/Presentation/Http/Resources/AgentCollectionListResource.php) يستخدم حالة Agent Settlement فقط، ويعرض `agent_net_due` واتجاهه إذا كان مطلوبًا للموبايل.

### 11.2 Settlement API

[`SettlementResource`](../app/Modules/Collections/Presentation/Http/Resources/Admin/SettlementResource.php) وتقارير التسويات تضيف:

```text
payment_direction
payable_amount
```

وتحافظ على `net_amount` الموقّع. عدد البنود لجميع الحالات يأتي من `items_count`، لا من Query أهلية متغير.

قيم `payment_direction` حسب النوع والإشارة:

| نوع التسوية | `net_amount > 0` | `net_amount < 0` | `net_amount = 0` |
|---|---|---|---|
| Agent | `agent_to_system` | `system_to_agent` | `no_payment` |
| Company | `system_to_company` | `company_to_system` | `no_payment` |

في جميع الحالات:

```text
payable_amount = abs(net_amount)
```

هذا حقل عرض فقط، ولا يستبدل `net_amount` الموقّعة.

[`CompanySettlementDetailResource`](../app/Modules/Collections/Presentation/Http/Resources/Company/CompanySettlementDetailResource.php) يعرض `commission_amount` و`net_amount` من Settlement Item المحفوظ، وهما في هذا السياق عمولة النظام وصافي الشركة.

### 11.3 Company Wallet

[`CompanyOrderRepository::getWalletAggregates()`](../app/Modules/Orders/Infrastructure/Persistence/Repositories/CompanyOrderRepository.php) يتحول إلى:

```text
total_commissions = SUM(system_commission_amount)
total_net_due = SUM(company_net_due)
pending_settlement_amount = SUM(company_net_due not linked to company settlement item)
amount_payable_to_company = SUM(company_net_due WHERE company_net_due > 0)
amount_receivable_from_company = ABS(SUM(company_net_due WHERE company_net_due < 0))
```

`collected_today` وأي أرقام شركة أخرى تستخدم `company_net_due`، لا `agent_net_due`. يبقى `total_net_due` و`pending_settlement_amount` Signed، بينما يفصل حقلا payable/receivable الاتجاهين للعرض.

### 11.4 Admin and Reports

يجب تحديث:

```text
AdminCollectionRepository
AgentCollectionRepository
ReportsRepository
CompanyOrderRepository
GetCollectionsBalanceQuery
GetDashboardSummaryQuery
```

قواعد العرض:

1. تقارير Agent تستخدم `agent_commission_amount` و`agent_net_due`.
2. تقارير Company تستخدم `system_commission_amount` و`company_net_due`.
3. تقارير Admin تعرض الزوجين بدل حقل واحد مبهم.
4. Settlement summaries تُفصل حسب `settlement_type` و`payment_direction`.
5. لا تجمع Agent signed net وCompany net في Total واحد بلا دلالة.
6. التقارير تحافظ على الإشارة؛ لا تستخدم `abs()` إلا في حقل العرض `payable_amount`.
7. هامش النظام يُشتق من `SUM(system_commission_amount - agent_commission_amount)` ولا يعاد احتسابه من إعدادات المستخدمين الحالية.
8. تقارير Company Settlement تفصل `system_to_company` و`company_to_system` و`no_payment`، ولا تكتفي بصافي قد يخفي حجم كل اتجاه.
9. [`GetCollectionsBalanceQuery`](../app/Modules/Dashboard/Application/Queries/GetCollectionsBalanceQuery.php) يعرض إجمالي ما على النظام للشركات من الأرصدة الموجبة، وإجمالي ما على الشركات للنظام من القيم السالبة بصورة منفصلة، إضافة إلى صافي موقّع إن لزم.
10. [`GetDashboardSummaryQuery`](../app/Modules/Dashboard/Application/Queries/GetDashboardSummaryQuery.php) لا يستخدم مجموع Balance وحده كمؤشر وحيد، لأن الموجب والسالب قد يتقاصان ويخفيا التعرض المالي في الاتجاهين.
11. موارد Company Profile وCompany Wallet تعرض `balance` الموقّع، وتضيف `balance_direction` و`balance_amount=abs(balance)` بصورة ثابتة للوضوح في واجهة المستخدم.

### 11.5 Breaking API Rename

القرار المعتمد هو Breaking Rename في الإصدار نفسه. يجب تحديث تطبيقات الموبايل والداشبورد بالتزامن مع نشر الـBackend.

القواعد الملزمة:

1. إزالة مفاتيح Collection القديمة `commission_amount` و`net_due` و`settlement_id` من الاستجابات المتأثرة.
2. استخدام الأسماء الصريحة `agent_commission_amount` و`agent_net_due` و`system_commission_amount` و`company_net_due`.
3. استخدام حالتي `agent_settlement_status` و`company_settlement_status` بدل حالة تسوية عامة للـCollection.
4. عدم إضافة aliases للأسماء القديمة.
5. عدم إنشاء compatibility layer أو version fallback.
6. عدم إبقاء فترة deprecation؛ يتغير عقد الـAPI مرة واحدة مع تحديث جميع العملاء.
7. تحديث Postman collection وAPI documentation واختبارات العقود في الإصدار نفسه.

---

## 12. السيدرات والنماذج والملفات المتأثرة

### 12.1 Models and migrations

```text
database/migrations/2026_05_19_144502_create_collections_table.php
database/migrations/2026_05_19_142747_create_order_financials_table.php
database/migrations/2026_05_19_144340_create_settlements_table.php
database/migrations/2026_06_25_190000_add_cash_received_fields_to_collections_table.php
database/migrations/...create_settlement_items_table.php
app/Modules/Collections/Infrastructure/Database/Models/Collection.php
app/Modules/Collections/Infrastructure/Database/Models/Settlement.php
app/Modules/Collections/Infrastructure/Database/Models/SettlementItem.php
app/Modules/Orders/Infrastructure/Database/Models/OrderFinancial.php
app/Modules/Dashboard/Infrastructure/Database/Models/OrderFinancial.php
app/Modules/Users/Infrastructure/Database/Models/DeliveryAgent.php
app/Modules/Users/Infrastructure/Database/Models/ShippingCompany.php
```

### 12.2 Financial write path

```text
CommissionCalculatorService
RecordCollectionService (new shared service)
OrderStatusChangeService
ReviewApprovalRequestUseCase
SettlementRepository and interface
CreateSettlementUseCase
MarkSettlementPaidUseCase
AdminCollectionRepository / MarkCashReceivedUseCase verification only
```

### 12.3 Read path

```text
AdminCollectionRepository
AgentCollectionRepository
CompanyOrderRepository
ReportsRepository
UserRepository
Dashboard collection and summary queries
Collection, Settlement, Company Wallet, Order Detail, and Report Resources
Settlement events/notifications that currently expose raw net amount
```

### 12.4 Seeders

[`OrderSeeder`](../database/seeders/OrderSeeder.php) يحسب حاليًا بيانات `order_financials` من عمولة الشركة، لكنه لا ينشئ Collections منطقية متطابقة معها. يجب توحيده مع الخدمة أو على الأقل مع المعادلات والـsnapshots الجديدة.

قيم Balance الثابتة في Agent وCompany seeders يجب مراجعتها؛ الأفضل أن تبدأ بصفر ثم تُشتق من Collections غير المسواة حتى تحقق invariants بدل أرقام لا ترتبط بالبيانات المزروعة.

---

## 13. خطة الاختبارات قبل تعديل المنطق المالي

يجب كتابة اختبارات التسوية والفشل أولًا، ثم تعديل `SettlementRepository`.

### 13.1 Calculation and snapshot

1. `C=1000, Ca=50, Cs=80` ينتج `agent_net_due=950` و`company_net_due=920` و`system_margin=30`.
2. `C=30, Ca=50` ينتج `agent_net_due=-20` دون Clamp.
3. `C=1000, Cs=80` ينتج `company_net_due=920`.
4. `C=30, Cs=50` ينتج `company_net_due=-20` دون Clamp أو Reject.
5. تغيير عمولة Agent بعد إنشاء Collection لا يغير الأعمدة المحفوظة.
6. تغيير عمولة Company بعد إنشاء Collection لا يغير الأعمدة المحفوظة.
7. تعديل مبلغ Collection مفتوحة يعيد الحساب من commission snapshots القديمة ويعدل الرصيد بالفرق فقط.
8. المسار العادي ومسار الموافقة ينتجان الصفوف والأرصدة نفسها.

### 13.2 Balance lifecycle

1. إنشاء Collection يزيد Agent balance بـ`agent_net_due` لا بـ`collected_amount`.
2. إنشاء Collection يزيد Company balance بـ`company_net_due`.
3. `mark-cash-received` لا يغير أي Balance.
4. Agent Settlement الموجبة تخفض الرصيد إلى صفر عند Paid.
5. Agent Settlement السالبة تزيد الرصيد السالب إلى صفر عند Paid، بمعنى System -> Agent.
6. عدة Collections موجبة وسالبة تُجمع مع الحفاظ على صافي الإشارة.
7. Agent Settlement بعد Cash Handover لا يكرر الأثر المالي.
8. Company Collection موجبة تزيد رصيد الشركة الموجب، وCompany Settlement Paid تعيده إلى صفر باستخدام `balance -= net_amount`.
9. Company Collection سالبة تزيد رصيد الشركة بـ`-20`، وCompany Settlement Paid تعيده إلى صفر باستخدام `-20 - (-20)`.
10. لا تستخدم `abs()` في تخزين أو تجميع أو خصم `net_amount` لأي نوع تسوية.
11. عكس Collection مفتوحة يزيل القيم الموقعة الصحيحة من الرصيدين.
12. `getDeliveryAgentActualBalance()` لا يخصم العمولة مرتين؛ بعد إزالته يعرض الـResource الرصيد الخام الموقّع.

### 13.3 Eligibility and directions

1. Agent Collection موجبة غير مؤهلة قبل `cash_received_at`.
2. Agent Collection سالبة مؤهلة دون `cash_received_at`.
3. Agent Collection صفرية مؤهلة دون Cash Handover.
4. `payment_direction=agent_to_system` للصافي الموجب.
5. `payment_direction=system_to_agent` للصافي السالب.
6. `payment_direction=no_payment` للصفر.
7. Company Collection موجبة غير مؤهلة قبل `cash_received_at`.
8. Company Collection سالبة مؤهلة دون `cash_received_at`.
9. Company Collection صفرية مؤهلة دون `cash_received_at`.
10. Company Settlement موجبة تعرض `payment_direction=system_to_company`.
11. Company Settlement سالبة تعرض `payment_direction=company_to_system`.
12. Company Settlement صفرية تعرض `payment_direction=no_payment`.
13. `payable_amount=abs(net_amount)` للعرض فقط في Agent وCompany.
14. API والتقارير لا تفقد علامة السالب.

### 13.4 Settlement items and concurrency

1. نفس Collection تدخل Agent Settlement ثم Company Settlement.
2. لا تدخل Collection تسويتين من النوع نفسه.
3. Draft تحفظ Item snapshots والعدد والمجاميع لحظة الإنشاء.
4. Collection جديدة في الفترة بعد إنشاء Draft لا تدخلها عند `markPaid()`.
5. تغيير بيانات Collection بعد Draft مرفوض ولا يغير التسوية.
6. طلبا إنشاء Draft متزامنان لا يحجزان البنود نفسها.
7. `markPaid()` يستخدم Items ولا يعيد Query حسب الفترة.
8. فساد مجموع Settlement مقارنة بـItems يمنع الدفع.

### 13.5 Completion and reports

1. `is_settled=false` بعد Agent Paid فقط.
2. `is_settled=false` بعد Company Paid فقط.
3. `is_settled=true` بعد اكتمال الطرفين Paid.
4. Draft أو Approved غير المدفوعة لا تحقق شرط `is_settled`.
5. Company Wallet تعرض عمولة النظام وصافي الشركة.
6. Agent reports تعرض عمولة المندوب وصافي المندوب الموقّع.
7. Settlement summaries تفصل الاتجاهات ولا تلغي القيم الموجبة والسالبة في رقم مضلل.
8. حظر حذف Agent يعمل عندما يكون Balance سالبًا أو موجبًا.
9. حظر حذف Shipping Company يعمل عندما يكون `balance <> 0` سواء كان موجبًا أو سالبًا.
10. Dashboard يفصل ما يدين به النظام للشركات عما تدين به الشركات للنظام.
11. تقارير Company Settlement تفصل المجاميع حسب `payment_direction` بدل الاكتفاء بصافي ملغٍ للاتجاهات.
12. Contract tests تثبت وجود الأسماء المالية الجديدة وغياب `commission_amount` و`net_due` و`settlement_id` القديمة من Collection responses.

---

## 14. ترتيب التنفيذ المقترح

### المرحلة 0: القرارات المقفلة

1. Option B باستخدام `settlement_items` هو التصميم الوحيد المنفذ.
2. الـAPI يستخدم Breaking Rename بلا compatibility layer.
3. Agent وCompany balances والتسويات Signed في الاتجاهين.
4. `cash_received_at` إثبات فقط وPaid Settlement هو قيد إغلاق الرصيد.

### المرحلة 1: اختبارات الحماية

1. إنشاء اختبارات Collections/Settlements الحالية التي تثبت السلوك المرغوب.
2. إضافة سيناريوهات signed Agent وCompany balances وno double deduction.
3. إضافة اختبارات snapshot وdraft immutability.

### المرحلة 2: Schema and models

1. تعديل هجرات الإنشاء لأن البيئة ستستخدم `migrate:fresh --seed`.
2. إنشاء `settlement_items`.
3. تحديث Models وcasts والعلاقات والتعليقات والفهارس.
4. تحديث migration تبعية `after('settlement_id')`.

### المرحلة 3: Unified collection write path

1. توسيع الحاسبة.
2. إنشاء RecordCollectionService.
3. تحويل مساري الحالة والموافقة إلى الخدمة المشتركة.
4. تحديث `order_financials` والأرصدة والعكس.

### المرحلة 4: Settlement workflow

1. إنشاء Draft وItems ذريًا.
2. تطبيق أهلية Agent وCompany الموقعة.
3. تحويل approve وmarkPaid إلى Items الثابتة.
4. دعم اتجاهات الدفع الثلاثة لكل نوع و`net_amount=0`.
5. تطبيق اكتمال التسويتين لـ`is_settled`.

### المرحلة 5: Reads and breaking API migration

1. تحديث Repositories والتقارير والداشبورد.
2. تحديث Resources ومفاتيح API وحذف المفاتيح القديمة.
3. إزالة الحساب المكرر من Agent profile.
4. تحديث حواجز حذف المستخدم.
5. تحديث الأحداث والإشعارات ذات المبالغ الموقعة.
6. تحديث الموبايل والداشبورد وPostman وAPI documentation في الإصدار نفسه.

### المرحلة 6: Seed and verification

1. توحيد Seeders مع invariants الجديدة.
2. تشغيل `php artisan migrate:fresh --seed`.
3. تشغيل الاختبارات الآلية.
4. تنفيذ دورة Postman كاملة للتسويتين على Collection واحدة.

---

## 15. تقدير الحجم

التقدير التالي للخيار المعتمد فقط: `settlement_items`، Signed positions للطرفين، وBreaking API Rename بلا compatibility layer.

| الجزء | التقدير |
|---|---:|
| ملفات متأثرة | 46-56 |
| وقت تنفيذ واختبار | 42-54 ساعة |
| مدة عملية | 6-8 أيام |
| المخاطرة المتبقية | متوسطة، ومحصورة أساسًا في ترحيل الـAPI واختبارات التزامن والتجميع ثنائي الاتجاه |

لا يشمل التقدير بناء compatibility layer لأنه مرفوض. يشمل تحديث عقود الـAPI والـResources وPostman والاختبارات، بينما يعتمد تقدير تطبيقات الموبايل والداشبورد الفعلي على حجم استخدام المفاتيح القديمة خارج هذا المستودع.

---

## 16. معايير القبول النهائية

يعتبر التنفيذ صحيحًا فقط عند تحقق جميع الآتي:

1. شركة الشحن تحصل على `C-Cs` بلا خصم `Ca`.
2. Collection تحفظ عمولتي Agent وSystem بصورة مستقلة وثابتة.
3. Agent balance يقبل الموجب والصفر والسالب ويحمل المعنى الموثق.
4. Company balance يقبل الموجب والصفر والسالب ويحمل المعنى الموثق.
5. `company_net_due=C-Cs` دائمًا بلا Clamp أو Reject بسبب الإشارة.
6. `mark-cash-received` لا يعدّل Balance.
7. Agent Settlement Paid تصفّر Signed Net Position باستخدام `balance -= signed net_amount`.
8. Company Settlement Paid تصفّر Signed Net Position باستخدام `balance -= signed net_amount`.
9. Agent Collection السالبة لا تُحجب بسبب غياب `cash_received_at`.
10. Company Collection الصفرية أو السالبة لا تُحجب بسبب غياب `cash_received_at`.
11. Company Settlement تدعم `system_to_company` و`company_to_system` و`no_payment`.
12. `abs()` تستخدم فقط في `payable_amount` للعرض، ولا تدخل التخزين أو الحساب المحاسبي.
13. الـAPI والتقارير تعرض اتجاه الدفع وتحافظ على الإشارة.
14. Dashboard والتقارير تفصل الالتزامات الموجبة والسالبة حسب الاتجاه.
15. لا يمكن حذف Shipping Company إذا كان `balance <> 0`.
16. نفس Collection تدخل تسويتي Agent وCompany مستقلتين.
17. Draft Settlement لا تتغير بنودها بين الإنشاء والدفع.
18. `is_settled` لا يصبح true إلا بعد Paid للطرفين.
19. لا توجد إعادة قراءة لإعدادات العمولة عند التسوية أو التقارير.
20. لا يوجد Double Deduction بين Cash Handover وAgent Settlement.
21. كل Settlement تعتمد حصريًا على `settlement_items`، ولا توجد split settlement FKs داخل `collections`.
22. استجابات Collection تستخدم الأسماء الجديدة فقط ولا تعرض aliases للمفاتيح القديمة.
23. لا توجد compatibility layer أو deprecation period للـAPI القديم.
