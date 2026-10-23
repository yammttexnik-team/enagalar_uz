# enagalar.uz — so'rovnoma savollari

> Bu savollar bazaga seed qilingan (`SurveySeeder`). O'zgartirish: admin panel → So'rovnomalar.

> Dasturchi shu bo'limdan `/sorovnoma` ga seed qiladi. Har savolda: `key` (unikal), `type` (single / multi / text / number / scale), `required`, matn UZ va RU, variantlar `kod = UZ / RU`. `max=3` — ko'pi bilan 3 ta tanlash. `source=regions` — viloyat/tuman bazadan. `check` — diqqat savoli (noto'g'ri bo'lsa bonus berilmaydi). Ovozli javob (text turida) keyin.
> **Rozilik (1-ekran, majburiy):** UZ — "Javoblaringiz enagalar.uz platformasini yaxshilash uchun ishlatiladi. Natijalar faqat umumiy (ismsiz) koʻrinishda eʼlon qilinadi. Telefon raqamingiz bonusni hisoblash uchun saqlanadi va uchinchi shaxsga berilmaydi. Istagan payt oʻchirishni soʻrashingiz mumkin. Bonus platforma ichida ishlatiladi, naqdga aylanmaydi." RU — «Ответы используются для улучшения enagalar.uz. Результаты публикуются только в обобщённом виде. Телефон хранится для начисления бонуса и не передаётся третьим лицам. Можно в любой момент попросить удалить данные. Бонус действует внутри платформы, не обналичивается.»
> **Yakuniy qoida:** toʻliq tugaganda avtomatik 50 000 bonus; 1 Telegram akkaunt = 1 marta; min. vaqt 4 daqiqa (undan tez — "tekshiruvga" belgilanadi, bonus ushlab turiladi); admin panelda shubhali javoblarni bekor qilish.

### Enaga

| № | key | type | required | UZ | RU | Variantlar (kod = UZ / RU) |
|---|---|---|---|---|---|---|
| 1 | n_region | single | yes | Qaysi viloyat va tumanda ishlaysiz? | В каком регионе и районе вы работаете? | source=regions |
| 2 | n_age | single | yes | Yoshingiz? | Ваш возраст? | a1 = 18–24; a2 = 25–34; a3 = 35–44; a4 = 45–54; a5 = 55+ |
| 3 | n_exp | single | yes | Enagalik tajribangiz qancha? | Ваш опыт работы няней? | e0 = 1 yildan kam / менее 1 года; e1 = 1–3 yil / 1–3 года; e2 = 3–5 yil / 3–5 лет; e3 = 5–10 yil / 5–10 лет; e4 = 10+ yil / 10+ лет |
| 4 | n_formats | multi | yes | Qaysi formatda ishlaysiz? | В каком формате работаете? | hour = soatbay / почасово; day = kunlik / посуточно; month = oylik / помесячно; live = yashab turish / с проживанием; weekend = hafta oxiri / выходные |
| 5 | n_price_hour | number | no | Hozir yoki oxirgi ishda soatiga qancha olasiz? (soʻm) | Сколько получаете в час? (сум) | min=0 |
| 6 | n_price_day | number | no | Kuniga qancha? (soʻm) | В день? (сум) | min=0 |
| 7 | n_price_month | number | no | Oyiga qancha? (soʻm) | В месяц? (сум) | min=0 |
| 8 | n_price_want | number | yes | Oyiga qancha olishni xohlaysiz? (soʻm) | Сколько хотели бы получать в месяц? (сум) | min=0 |
| 9 | n_find_job | multi | yes | Soʻnggi ishingizni qanday topdingiz? | Как нашли последнюю работу? | tg = Telegram kanal / Telegram-канал; friend = tanish-urugʻ / знакомые; agency = agentlik / агентство; olx = OLX; bolambilan = bolambilan.uz; other = boshqa / другое |
| 10 | n_contacts_3m | number | yes | Oxirgi 3 oyda nechta oilaga yozdingiz? | Скольким семьям вы писали за 3 месяца? | min=0 |
| 11 | n_replies_3m | number | yes | Shulardan nechtasi javob berdi? | Сколько из них ответили? | min=0 |
| 12 | n_problems | multi (max=3) | yes | Ish qidirishdagi eng katta 3 muammo? | Топ-3 проблемы поиска работы? | few = ish kam / мало работы; old = eski yoki soxta eʼlonlar / старые или фейковые объявления; noreply = javob bermaydilar / не отвечают; lowpay = maosh kam / низкая оплата; delay = pulni kechiktirish / задержка оплаты; safety = xavfsizlik / безопасность; where = qayerda qidirishni bilmayman / не знаю, где искать |
| 13 | n_paid_ad | single | yes | Telegram kanalga eʼlon berish uchun pul toʻladingizmi? | Платили ли вы за размещение в Telegram-канале? | no = Yoʻq / Нет; free = Bepul berdim / Размещала бесплатно; paid = Ha, toʻladim / Да, платила |
| 14 | n_danger | text | yes | Ishda xavfli, nohush yoki adolatsiz holat boʻlganmi? Qisqacha yozing ("yoʻq" ham mumkin). | Были ли на работе небезопасные или неприятные ситуации? Кратко (можно «нет»). | minwords=1 |
| 15 | n_family_info | multi | yes | Oila haqida oldindan nimani bilishni xohlaysiz? | Что хотели бы знать о семье заранее? | kids = bolalar yoshi / возраст детей; district = tuman / район; pay = maosh / оплата; schedule = jadval / график; prev = oldingi enaga nega ketgan / почему ушла прошлая няня; cam = uyda kamera bormi / есть ли камеры; reviews = oila haqida sharhlar / отзывы о семье |
| 16 | n_verify_ready | scale | yes | Pasport bilan "Tasdiqlangan" nishonini olishga tayyormisiz? (1 = yoʻq, 5 = tayyorman) | Готовы ли пройти проверку паспорта? (1 = нет, 5 = готова) | 1..5 |
| 17 | n_verify_fear | text | no | Tekshiruvdan xavotir bor boʻlsa, nimadan? | Чего опасаетесь при проверке? | |
| 18 | n_docs | multi | yes | Qaysi hujjatlarni bera olasiz? | Какие документы можете предоставить? | passport = pasport / паспорт; med = tibbiy maʼlumotnoma / медсправка; dispensary = dispanser maʼlumotnomasi / справка из диспансера; cert = sertifikat / сертификат; refs = tavsiyanoma / рекомендации |
| 19 | n_features | multi (max=3) | yes | Platformada eng muhim 3 narsa? | Что важнее всего на платформе? | chat = ichki chat / чат внутри; ai = AI anketa yozuvchi / AI-помощник для анкеты; badges = tasdiqlangan nishonlar / значки проверки; fast = tez javob / быстрые ответы; translate = tarjima / перевод; guarantee = toʻlov kafolati / гарантия оплаты |
| 20 | n_pay_top10 | single | yes | TOP eʼlon (7 kun) 10 000 soʻm boʻlsa, sinab koʻrasizmi? | Попробуете ТОП-объявление (7 дней) за 10 000 сум? | yes = Ha / Да; no = Yoʻq / Нет |
| 21 | n_pay_top25 | single | yes | 25 000 soʻm boʻlsa-chi? | А за 25 000 сум? | yes; no |
| 22 | n_pay_top40 | single | yes | 40 000 soʻm boʻlsa-chi? | А за 40 000 сум? | yes; no |
| 23 | n_pay_pro_max | number | yes | PRO anketa uchun oyiga maksimal qancha toʻlay olasiz? (soʻm, 0 = toʻlamayman) | Сколько максимум готовы платить за PRO-анкету в месяц? (сум, 0 = не буду) | min=0 |
| 24 | n_check | single | yes | (Diqqat savoli) Shu savolda "Ikkinchi variant"ni tanlang. | (Проверка внимания) Выберите «второй вариант». | a = Birinchi / Первый; b = Ikkinchi / Второй; c = Uchinchi / Третий; check=b |
| 25 | n_ideal | text | yes | Ideal ish qidirish jarayoni uchun bitta narsa nima boʻlardi? | Что одно сделало бы поиск работы идеальным? | minwords=5 |

### Ota-ona

| № | key | type | required | UZ | RU | Variantlar (kod = UZ / RU) |
|---|---|---|---|---|---|---|
| 1 | p_region | single | yes | Qaysi viloyat va tumandasiz? | В каком регионе и районе вы? | source=regions |
| 2 | p_kids | number | yes | Nechta bolangiz bor (enaga kerak boʻladigan)? | Сколько у вас детей (для которых нужна няня)? | min=1 |
| 3 | p_kid_age | multi | yes | Bolalar yoshi? | Возраст детей? | b0 = 0–1; b1 = 1–3; b2 = 3–6; b3 = 6+ |
| 4 | p_status | single | yes | Hozir enaga bormi? | Есть ли сейчас няня? | have = Bor / Есть; seeking = Qidiryapman / Ищу; soon = Yaqinda kerak boʻladi / Скоро понадобится |
| 5 | p_formats | multi | yes | Qanday format kerak? | Какой формат нужен? | hour = soatbay / почасово; day = kunlik / посуточно; month = oylik / помесячно; live = yashab turish / с проживанием; half = yarim kun / полдня |
| 6 | p_budget_hour | number | no | Byudjet: soatiga (soʻm) | Бюджет: в час (сум) | min=0 |
| 7 | p_budget_day | number | no | Byudjet: kuniga (soʻm) | Бюджет: в день (сум) | min=0 |
| 8 | p_budget_month | number | no | Byudjet: oyiga (soʻm) | Бюджет: в месяц (сум) | min=0 |
| 9 | p_find | multi | yes | Enagani qanday qidirdingiz? | Как искали няню? | tg = Telegram kanal / Telegram-канал; friend = tanish / знакомые; agency = agentlik / агентство; olx = OLX; bolambilan = bolambilan.uz; insta = Instagram; other = boshqa / другое |
| 9a | p_days | number | yes | Oxirgi safar necha kun qidirdingiz? | Сколько дней искали в последний раз? | min=0 |
| 10 | p_cands | number | yes | Nechta nomzod koʻrdingiz? | Сколько кандидатов рассмотрели? | min=0 |
| 11 | p_met | number | yes | Nechtasi bilan uchrashdingiz? | С сколькими встретились? | min=0 |
| 12 | p_ad_result | text | no | Telegram kanalga eʼlon bergan boʻlsangiz: nechta javob keldi, nechtasi yaroqli edi? | Если давали объявление: сколько откликов, сколько подходящих? | |
| 13 | p_problems | multi (max=3) | yes | Eng katta 3 muammo? | Топ-3 проблемы? | distrust = ishonchsizlik / недоверие; unknown = tajribasi nomaʼlum / опыт неизвестен; time = vaqt koʻp ketadi / уходит много времени; price = narx / цена; old = eski eʼlonlar / старые объявления; noreply = javob bermaydilar / не отвечают; fake = yolgʻon maʼlumot / ложные данные; agency = agentlik qimmat / агентство дорого |
| 14 | p_fear | text | yes | Bolangizni begona kishiga topshirishda sizni eng koʻp nima qoʻrqitadi? | Чего вы больше всего боитесь, доверяя ребёнка няне? | minwords=3 |
| 15 | p_checks | multi | yes | Qaysi tekshiruvlar siz uchun muhim? | Какие проверки важны? | passport = pasport / паспорт; med = tibbiy / медицинская; dispensary = dispanser / диспансер; record = sudlanmaganlik / несудимость; refs = tavsiyanoma / рекомендации; video = video tanishuv / видеознакомство; cert = sertifikat / сертификат |
| 16 | p_bad | text | no | Yomon yoki yoqimsiz tajriba boʻlganmi? Qisqacha ("yoʻq" ham mumkin). | Был ли плохой опыт? Кратко (можно «нет»). | |
| 17 | p_agency | single | yes | Agentlikka murojaat qilganmisiz? | Обращались ли в агентство? | no = Yoʻq / Нет; yes = Ha / Да |
| 18 | p_agency_pay | number | no | Agentlikka qancha toʻlagansiz yoki toʻlashga tayyormisiz? (soʻm) | Сколько заплатили или готовы заплатить агентству? (сум) | min=0 |
| 19 | p_features | multi (max=3) | yes | Platformada eng muhim 3 narsa? | Что важнее всего на платформе? | badges = nishonlar / значки проверки; chat = ichki chat / чат внутри; ai = AI mos nomzod / AI-подбор; fast = tez javob / быстрые ответы; reviews = sharhlar / отзывы; guarantee = toʻlov kafolati / гарантия оплаты; video = video / видео |
| 20 | p_sub15 | single | yes | Obuna (oyiga, cheksiz chat + AI) 15 000 soʻm boʻlsa, olasizmi? | Купили бы подписку (в месяц, безлимитный чат + AI) за 15 000 сум? | yes = Ha / Да; no = Yoʻq / Нет |
| 21 | p_sub35 | single | yes | 35 000 soʻm boʻlsa-chi? | А за 35 000 сум? | yes; no |
| 22 | p_sub60 | single | yes | 60 000 soʻm boʻlsa-chi? | А за 60 000 сум? | yes; no |
| 23 | p_pay_max | number | yes | Oyiga maksimal qancha toʻlaysiz? (soʻm, 0 = toʻlamayman) | Сколько максимум заплатите в месяц? (сум, 0 = не буду) | min=0 |
| 24 | p_check | single | yes | (Diqqat savoli) Shu savolda "Uchinchi variant"ni tanlang. | (Проверка внимания) Выберите «третий вариант». | a = Birinchi / Первый; b = Ikkinchi / Второй; c = Uchinchi / Третий; check=c |
| 25 | p_ideal | text | yes | Ideal enaga qidirish tajribasi qanday boʻlardi? | Каким был бы идеальный поиск няни? | minwords=5 |

> Vaqt: ~10–12 daqiqa. Natijalar qarorlarga qanday taʼsir qilishi — Ilova B (3.5).
