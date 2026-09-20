# Revised Implementation Plan Request

الخطة الأساسية جيدة ومطابقة لفكرة فصل عمولة المندوب عن عمولة النظام، لكن أريد **Revise للخطة قبل أي تنفيذ** وفق النقاط التالية. لا تعدل أي كود الآن، المطلوب نسخة محسنة من الخطة فقط.

## أولًا — ثبّت قواعد العمل الأساسية كما هي

لدينا عمولتان مستقلتان تمامًا:

```text
C  = collected_amount
Ca = delivery_agents.commission_value
Cs = shipping_companies.commission_value
```

عمولة المندوب تخص العلاقة:

```text
Delivery Agent ↔ System
```

وعمولة النظام تخص العلاقة:

```text
System ↔ Shipping Company
```

وبالتالي:

```text
agent_net_due   = C - Ca
company_net_due = C - Cs
system_margin   = Cs - Ca
```

مثال:

```text
Order = 1000
Agent Commission = 50
System Commission = 80

Agent Net Due   = 950
Company Net Due = 920
System Margin   = 30
```

إذن شركة الشحن تحصل على **920 وليس 870**. لا يتم خصم عمولة المندوب من مستحق شركة الشحن.

---

## 1) غيّر تعريف `delivery_agents.balance` ليكون Signed Net Financial Position وليس Cash in Hand

لا أريد أن يمثل `delivery_agents.balance` إجمالي النقد الفعلي الموجود في يد المندوب.

أريده أن يمثل **صافي المركز المالي بين المندوب والنظام**.

اعتمد Convention واحدة واضحة:

```text
delivery_agent.balance > 0
→ المندوب مدين للنظام

delivery_agent.balance = 0
→ لا يوجد صافي مستحق بين الطرفين

delivery_agent.balance < 0
→ النظام مدين للمندوب
```

وعند إنشاء Collection:

```text
balance += agent_net_due
```

مثال طبيعي:

```text
collected_amount = 1000
agent_commission = 50
agent_net_due = 950

delivery_agent.balance += 950
```

فيصبح المندوب مدينًا للنظام بـ950.

مثال تكون فيه عمولة المندوب أكبر من المبلغ المحصل:

```text
collected_amount = 30
agent_commission = 50
agent_net_due = -20
```

هنا:

```text
delivery_agent.balance += -20
```

والقيمة `-20` صحيحة ومقصودة، وتعني أن **النظام أصبح مدينًا للمندوب بـ20**.

لا تحول القيمة السالبة إلى صفر ولا تعتبرها خطأ.

---

## 2) راجع `mark-cash-received` بناءً على تعريف الرصيد الجديد

النظام لديه بالفعل `cash_received_at` و`cash_received_by` لإثبات أن الإدارة استلمت النقد من المندوب، وهذه خطوة منفصلة موجودة حاليًا في دورة التحصيل.

لذلك أريد أن تشرح في الخطة بوضوح الفرق بين:

```text
Collection
Cash Handover / mark-cash-received
Agent Settlement
```

لا أريد أن تؤثر عمليتان مختلفتان على نفس الرصيد وتنتج Double Deduction.

إذا كان:

```text
delivery_agent.balance = net financial position
```

فيجب تحديد **بالضبط** أي Event يقوم بتسوية الالتزام:

- متى يزيد الرصيد؟
- متى ينخفض؟
- ماذا يحدث عند `mark-cash-received`؟
- ماذا يحدث لاحقًا عند `markPaid()` للـAgent Settlement؟

لا تجعل `mark-cash-received` و`markPaid()` يخصمان نفس الالتزام مرتين.

بما أن دورة العمل الحالية تسجل استلام النقد قبل إنشاء/دفع التسوية، أريد أن تراجع هل الأفضل أن يكون Cash Handover هو الحدث الذي يؤثر على المركز المالي، بينما Agent Settlement يصبح عملية تجميع/مطابقة واعتماد، أم أن هناك تعريفًا آخر أنسب. المهم أن يكون هناك **Event واحد فقط لكل أثر مالي**.

---

## 3) يجب دعم اتجاهين في Agent Settlement وليس افتراض Agent → System دائمًا

بسبب السماح بالقيم السالبة، لم يعد صحيحًا افتراض أن المندوب دائمًا سيدفع للنظام.

اجعل:

```text
agent_settlement_net = SUM(agent_net_due)
```

إذا كانت:

```text
agent_settlement_net > 0
```

فهذا يعني:

```text
Agent → System
```

وإذا كانت:

```text
agent_settlement_net < 0
```

فهذا يعني:

```text
System → Agent
```

بمبلغ:

```text
abs(agent_settlement_net)
```

وإذا كانت صفرًا فلا يوجد صافي دفع.

يجب أن تدعم الـAPI والتقارير والتسوية هذه الإشارة ولا تفقدها.

كذلك راجع شرط أهلية Collections للـAgent Settlement، لأن النظام الحالي يعتمد على `cash_received_at IS NOT NULL`. إذا كان `agent_net_due` سالبًا، فلا يوجد أصلًا Cash مطلوب استلامه من المندوب؛ بل النظام هو المدين له. لذلك لا يجوز أن يجعل شرط `cash_received_at` هذه الحالات غير قابلة للتسوية. أريد حلًا واضحًا لهذه الحالة في الخطة.

---

## 4) احذف `max(0, ...)` بالنسبة لعمولة المندوب

لا أوافق على الاقتراح الحالي:

```text
max(0, collected_amount - agent_commission_amount)
```

لأن القيمة السالبة لها معنى مالي مطلوب.

المعادلة يجب أن تظل حرفيًا:

```text
agent_net_due =
collected_amount - agent_commission_amount
```

ويمكن أن تكون:

```text
positive
zero
negative
```

القيمة السالبة تعني أن للمندوب مستحقًا عند النظام.

بالنسبة لحالة:

```text
system_commission_amount > collected_amount
```

في علاقة شركة الشحن، لا تتخذ قرارًا تلقائيًا بالـClamp إلى صفر قبل توضيح أثره؛ اذكرها كقاعدة Business منفصلة تحتاج اعتمادًا إذا كانت الحالة ممكنة.

---

## 5) ثبّت Snapshot للعمولتين وقت إنشاء الـCollection

أريد تأكيد هذا كمبدأ مالي صريح في الخطة.

عند إنشاء Collection يتم قراءة:

```text
delivery_agents.commission_value
shipping_companies.commission_value
```

مرة واحدة، ثم تخزين:

```text
agent_commission_amount
agent_net_due
system_commission_amount
company_net_due
```

داخل الـCollection.

وإذا تغيرت عمولة المندوب أو الشركة لاحقًا، لا تتغير Collections القديمة.

كل Settlement أو Report لاحق يعتمد على القيم المحفوظة داخل Collection، وليس على `commission_value` الحالية.

---

## 6) استمر في فصل Agent Settlement عن Company Settlement

هذه نقطة صحيحة في الخطة ولا أريد التراجع عنها.

نفس Collection يجب أن تستطيع المرور في:

```text
Agent Settlement
```

ثم بشكل مستقل:

```text
Company Settlement
```

في الحل الأدنى يمكن استخدام:

```text
agent_settlement_id
company_settlement_id
```

لكن اقرأ النقطة التالية قبل تثبيت هذا التصميم.

---

## 7) أريد إعادة تقييم `settlement_items` بدل الاكتفاء بتقسيم `settlement_id`

الخطة الحالية تحل مشكلة C1 عن طريق:

```text
agent_settlement_id
company_settlement_id
```

لكنها لا تحل جذريًا مشكلة أن الـSettlement Draft لا تحفظ البنود نفسها.

حاليًا عند إنشاء Draft يتم حفظ المجاميع، ثم عند `markPaid()` يعاد Query للـCollections. الخطة اقترحت مقارنة المجاميع وقت الدفع كـSafety Check.

هذا جيد كحاجز حماية، لكنه ليس Snapshot حقيقيًا.

أريد في النسخة المحسنة مقارنة واضحة بين حلين:

```text
Option A — Minimal
agent_settlement_id + company_settlement_id
+ reconciliation check at markPaid()
```

وبين:

```text
Option B — Recommended
settlement_items
```

بحيث يتم ربط Collections فعليًا بالتسوية عند إنشاء Draft، وتجميد:

```text
collection_id
gross_amount
commission_amount
net_amount
settlement_type
```

ولا تعاد إعادة اختيارها عند الدفع.

اذكر أثر كل اختيار على:

- حجم التعديل.
- سلامة البيانات.
- إمكانية Audit.
- التعامل مع concurrency.
- سهولة إضافة Reversal لاحقًا.

ثم أعطني توصيتك، ولا تنفذ أي خيار بعد.

---

## 8) `shipping_companies.balance` يجب أن يكون له تعريف مالي واضح

اجعل:

```text
shipping_companies.balance
= accumulated unpaid company net due
```

أي صافي ما يدين به النظام للشركة ولم يتم دفعه بعد.

بالتالي بشكل أساسي:

```text
عند نشوء Collection:
company.balance += company_net_due

عند Company Settlement Paid:
company.balance -= settlement.net_amount
```

مع تعريف واضح لما يحدث في Reversal.

---

## 9) راجع `getDeliveryAgentActualBalance()` بالكامل بعد تغيير معنى `balance`

الحل الحالي في الخطة:

```text
balance - SUM(agent_commission_amount)
```

لم يعد مناسبًا إذا أصبح `delivery_agents.balance` نفسه يمثل Net Financial Position.

في هذه الحالة لا أريد خصم العمولة مرة أخرى، لأن ذلك سيؤدي إلى Double Deduction.

راجع استخدامات:

```text
getDeliveryAgentActualBalance()
AgentProfileResource
```

وحدد هل:

- الدالة تصبح مجرد إرجاع للـbalance.
- أو يعاد تسميتها.
- أو تزال إذا أصبحت زائدة عن الحاجة.

مع الحفاظ على API compatibility إذا كان ذلك مطلوبًا.

---

## 10) `order_financials.is_settled` لا يصبح true إلا بعد اكتمال المرحلتين

احتفظ بهذه القاعدة:

```text
agent settlement complete
AND
company settlement complete
```

فقط عندها:

```text
is_settled = true
```

بعد واحدة فقط:

```text
is_settled = false
```

---

## 11) ثبّت معنى `settlements.total_collections / total_commissions / net_amount`

يمكن إبقاء نفس أسماء الأعمدة، لكن معناها يعتمد صراحة على `settlement_type`.

Agent Settlement:

```text
total_collections = SUM(collected_amount)
total_commissions = SUM(agent_commission_amount)
net_amount        = SUM(agent_net_due)
```

Company Settlement:

```text
total_collections = SUM(collected_amount)
total_commissions = SUM(system_commission_amount)
net_amount        = SUM(company_net_due)
```

مع السماح لـAgent `net_amount` أن يكون سالبًا.

---

## 12) وحّد مسار إنشاء الأثر المالي

لا أريد استمرار نسختين مختلفتين من حساب Collection في:

```text
OrderStatusChangeService::recordCollection()
```

و:

```text
ReviewApprovalRequestUseCase::applyApprovedFinancialEffect()
```

استخرج الحساب والكتابة المالية المشتركة إلى Service واحدة، ويستدعيها المساران، بحيث لا يمكن أن يحسب أحدهما Agent/System Commission بطريقة تختلف عن الآخر.

---

## 13) أضف Financial Definitions / Invariants في بداية الخطة الجديدة

أريد قسمًا مستقلًا يثبت المعاني التالية قبل الحديث عن الملفات:

```text
collected_amount
agent_commission_amount
agent_net_due
system_commission_amount
company_net_due
delivery_agents.balance
shipping_companies.balance
cash_received_at
Agent Settlement
Company Settlement
is_settled
```

وحدد اتجاه كل قيمة والإشارة الخاصة بها.

وبالأخص:

```text
agent_net_due > 0
Agent owes System

agent_net_due < 0
System owes Agent
```

وكذلك:

```text
delivery_agent.balance > 0
Agent owes System

delivery_agent.balance < 0
System owes Agent
```

---

## 14) وسّع الاختبارات قبل التنفيذ المالي

بالإضافة للاختبارات الموجودة في الخطة، أريد تغطية السيناريوهات التالية:

```text
C=1000, Ca=50, Cs=80
agent_net_due=950
company_net_due=920
```

واختبار:

- نفس Collection تدخل Agent Settlement ثم Company Settlement.
- تغيير Commission Settings بعد إنشاء Collection لا يغير القيم القديمة.
- Agent Settlement موجب: المندوب مدين للنظام.
- Agent Settlement سالب: النظام مدين للمندوب.
- Collection بقيمة 30 وعمولة مندوب 50 تنتج `agent_net_due=-20` بدون Clamp.
- عدة Collections موجبة وسالبة يتم تجميعها مع الحفاظ على صافي الإشارة.
- `mark-cash-received` لا ينتج Double Deduction.
- Agent Settlement بعد Cash Handover لا يكرر الأثر المالي.
- `getDeliveryAgentActualBalance()` لا يخصم العمولة مرتين.
- Company balance يزيد بـ`company_net_due` ويقل بنفس الأساس عند الدفع.
- `is_settled=false` بعد تسوية طرف واحد و`true` بعد الطرفين.
- Draft Settlement لا يمكن أن يتغير محتواه بصمت بين الإنشاء والدفع.

---

## 15) أعد تقدير حجم التعديل بعد هذه المراجعة

بعد تعديل الخطة أريد تقديرين إن لزم:

```text
Minimal Safe Version
```

و:

```text
Recommended Financially Safe Version
```

خصوصًا إذا كان الفرق بينهما هو إدخال `settlement_items`.

---

## المطلوب النهائي الآن

لا تنفذ أي كود ولا migrations. أعطني فقط نسخة **Revised Implementation Plan** بعد فحص تأثير هذه القرارات على المشروع الحالي.

ويجب أن تكون الخطة الجديدة واضحة بشكل خاص في ثلاثة أشياء:

1. **من هو المدين لمن عند كل مرحلة.**
2. **أي Event بالضبط يعدل كل Balance حتى لا يحدث Double Accounting.**
3. **كيف يتعامل النظام مع Agent Balance السالب باعتباره مستحقًا للمندوب، وليس خطأ أو قيمة تُحوّل إلى صفر.**
