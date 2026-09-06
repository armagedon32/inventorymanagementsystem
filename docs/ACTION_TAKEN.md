# ACTION TAKEN — Final Re-defense (August 24, 2026)

**Name:** CAWED, ERLY GRACE · **Program:** DIT
**Title:** Development of Inventory and Requisitions System with Asset Tracking and Data Analytics

> Items marked **[SYSTEM]** are answered by the actual running system (verified live). Items marked **[MANUSCRIPT]** are thesis-revision actions handled in the chapters/SOP indicated.

**Access during evaluation:** Primary link `https://knsinventorysystem.site/` (HTTPS) · Alternate `https://www.knsinventorysystem.site/` · Mirror `https://server-production-df0e.up.railway.app/`
**Credentials:** Admin `superadmin / admin123` · Faculty `faculty / intern123` · Staff `staff / assistant123`
**Docs:** `docs/USER_MANUAL.md`, `docs/IMPLEMENTATION_PLAN.md`, `docs/SYSTEM_ACCESS_AND_EVALUATION.md`

---

## 1. Dr. Isagani Tano (Chairman)

| Comment / Recommendation | Action Taken |
| --- | --- |
| Prepare the system manual. **[SYSTEM]** | A comprehensive **User Manual** was prepared (`docs/USER_MANUAL.md`) and included in the Appendices. It covers system overview, installation/deployment notes, system requirements, user roles, step-by-step instructions for every module (stock, requisitions, asset tracking/RIS, forecasting, reports, admin/backup), interpretation of outputs (e.g., forecast MAPE, stock status), and troubleshooting. |
| Develop the system implementation plan. **[SYSTEM]** | An **Implementation Plan** (`docs/IMPLEMENTATION_PLAN.md`) was developed: objectives and success criteria, technology stack, phases completed, release schedule, dataset and analytics methodology, deployment pipeline, operation/maintenance schedules, risk mitigation, and roadmap. |
| Present and publish the research paper. **[MANUSCRIPT]** | The revised manuscript (per all panel comments below) is being submitted for final checking; publication will follow the institutional journal/repository process. |

---

## 2. Dr. Jonilo Mababa

| Comment / Recommendation | Action Taken |
| --- | --- |
| Chapter 4 should present results, not repeat the system design. | **Chapter 4 was reorganized** to present research findings/results first, with system description limited to a one-section summary and cross-reference to the appendices. |
| Scalability: where is the evidence for 100 / 1,000 users or 100,000 transactions? | The manuscript claim was **revised to state only demonstrated scale**: the live system carries **100+ registered user accounts** and **3,782 recorded stock transactions over 48 months** (Sep 2022 – Aug 2026). The current **Evaluation Evidence** table (`docs/SYSTEM_ACCESS_AND_EVALUATION.md` §4) lists the exact counts; claims beyond tested scale were removed and moved to a "future work / capacity planning" note with concrete next steps (scheduled backups, index review). |
| Which cloud provider? Where is data stored? Data Privacy Act implications? | **[SYSTEM]** Documented in `docs/SYSTEM_ACCESS_AND_EVALUATION.md` §3–5: primary hosting **AWS EC2 (ap-southeast-2, Sydney, Australia)**; mirror on Railway. A **Data Privacy Act (R.A. 10173) alignment table** was added: bcrypt password hashing, 12-hour JWT sessions, captcha, role-based access, full audit log, data minimization, documented breach/restore procedure. The physical location outside the Philippines and the corresponding institutional DPA notification responsibility are explicitly discussed. |
| What backups? Explain further. | **[SYSTEM]** A **backup/restore facility** is implemented and documented (User Manual §9.3): Admin may download `custodian_backup-YYYY-MM-DD.db` (the server flushes the WAL before copying); restore validates the SQLite header and keeps a `pre_restore_<timestamp>.db` safety snapshot before replacing the database. Weekly download-and-off-site-storage is the established procedure in the Implementation Plan. |
| Distinguish descriptive dashboards vs. true data analytics. | **[SYSTEM]** Defined in `docs/SYSTEM_ACCESS_AND_EVALUATION.md` §6: dashboards summarize historical facts; the **data analytics** component is the **LSTM forecasting module** with an explicit target variable (monthly issuance quantity per product), a trained model over 48 months, and quantitative evaluation (MAPE/MAE/RMSE) producing 3-month forecasts and suggested reorder quantities. |
| Empirical evidence that the architecture resolves historical limitations? | The limitation of the manual process was unbounded processing delay and stock discrepancy. **Objective before-and-after evidence** is presented: the dataset reconciles with office records (48 monthly totals, exported in `forecast_data/*.csv`), approval auto-issuance eliminates double-hand entry, and forecast accuracy is measured at **MAPE 19.41%** (acceptance ≤ 20%) — a measurable outcome, not a perceived rating. |
| LSTM appears only once. | **[MANUSCRIPT]** LSTM is now explicitly named in Chapter 2 with a dedicated discussion (reason for choice vs. ARIMA/Exponential Smoothing/Prophet, comparison table added) and its methodology and evaluation in Chapter 3. See also the Cabrera section. |
| Comprehensive User Manual in Appendices. | **[SYSTEM]** Done — `docs/USER_MANUAL.md` (step-by-step, user roles, screenshots placeholders, troubleshooting) is prepared for inclusion in the Appendices. |
| Provide system credentials and link. | **[SYSTEM]** Link + credentials are provided above and in `docs/SYSTEM_ACCESS_AND_EVALUATION.md` §1–2. |

---

## 3. Dr. Jovy Jay Cabrera

| Comment / Recommendation | Action Taken |
| --- | --- |
| SOP 3 must specify the algorithm, target forecast variable, and evaluation metrics. | **[MANUSCRIPT]** SOP 3 was updated to state: **Algorithm = LSTM**; **target variable = monthly issuance quantity per product**; **evaluation metrics = RMSE, MAE, MAPE** with acceptance criterion **MAPE ≤ 20%**, plus an explicit **evaluation step** (hold-out comparison of predicted vs. actual). |
| No evaluation step. | The methodology now includes a **model evaluation step** (split history → train/validate with deterministic seed → compare forecast vs. actual → report RMSE/MAE/MAPE). See next rows. |
| Chapter 2: dedicated forecasting discussion — algorithm, variable, how the model processes history, why LSTM. | **[MANUSCRIPT]** Added to Chapter 2: algorithm identification, definition of the forecast target, the rolling 12-month window processing, and justification for LSTM (suitability for sequential demand data with trends/seasonality). |
| Explicit formulas (RMSE/MAE/MAPE), IEEE formatting, keyword equations with (1), (2)… ; text, not screenshots. | **[MANUSCRIPT]** Formulas for RMSE, MAE, and MAPE were **moved from Chapter 3 to Chapter 2** as methodology, rendered as editable equations in IEEE style with sequential right-aligned equation numbers (1)–(3). Chapter 3 now contains only the results discussion. |
| Comparison table of forecasting algorithms and LSTM justification. | **[MANUSCRIPT]** A **comparison table** (Naïve, Moving Average, Exponential Smoothing, ARIMA/SARIMA, LSTM) was added to Chapter 2's literature review with basis for selecting LSTM. |
| Expand dataset to at least 3 years. | **[SYSTEM]** **[DONE]** The dataset spans **48 months (Sep 2022 – Aug 2026)** — beyond the 3-year minimum — across **72 stock products**; per-calendar-year coverage is shown per product in the Demand Forecasting screen (`Historical Data Coverage by Year`). |
| Results must meet the required performance. | **[SYSTEM]** Live evaluation: **MAPE = 19.41%** (within the ≤ 20% acceptance band), reported per retraining run alongside MAE and RMSE in the Demand Forecasting screen. |

---

## 4. Dr. Edna Dayao

| Comment | Action Taken |
| --- | --- |
| **Overall:** APA format and LCUP format. | **[MANUSCRIPT]** Whole manuscript reformatted to **APA 7th edition, following the LCUP thesis/dissertation format template** (headings, citation, references, tables/figures labeling). |
| Stock Monitoring and Control — real-time monitoring of stock levels, movements, availability. | **[SYSTEM]** Present and demonstrated. Dashboard reflects live counts; `Stock Inventory` shows each item's current quantity, movement buttons (Stock In / Stock Out), history timeline, reorder level, and auto-computed status (In Stock / Low Stock / Out of Stock). Reports → Transactions shows every movement with date, quantity, and recipient. |
| Issuance and Return Management — issued items, expected returns, actual returns, item condition. | **[SYSTEM]** Implemented in **Asset Tracking (RIS)**: each issued item has an **expected return date (`end_datetime`)**, is flagged **Overdue** when past due without return, records the **actual return (`return_date`)**, and captures the **item condition** (Good/Fair/Damaged, etc.). Department Reports summarize Returned/Overdue. |
| Inventory Reports — daily, weekly, and monthly. | **[SYSTEM]** **[ADDED]** Reports → Transactions now has a period selector **Daily / Weekly / Monthly** (daily = last 30 days, weekly = last 12 weeks, monthly = 48 months) in addition to the year filter and CSV export. Dashboard also provides the 48-month issuance view. |
| Accountability — specific custodian or accountable person per item. | **[SYSTEM]** Every asset can be **assigned to a specific person/instructor/office** (assignment record: custodian, department, date, remarks); RIS tracks each borrower; department reports show Returned/Overdue per custodian. |
| Complete documentation link, AVP/video, user manual. | **[SYSTEM, PARTIAL]** Documentation link (this `docs/` bundle + User Manual) and **credentials** are provided. **AVP/video**: final recording is under production and will be submitted with the revised User's Manual. |
| Request tracking and notification system. | **[SYSTEM]** **[ADDED]** A **request notification system** was implemented in the live system: a bell icon with an unread badge; Admins are notified when a requisition is created, and the requester is notified when it is approved or rejected (with the reason). Mark-all-as-read and per-item read are supported. |
| Send system link + test account credentials with appropriate roles. | **[SYSTEM]** Provided above and in `docs/SYSTEM_ACCESS_AND_EVALUATION.md`; accounts cover Admin (full access for evaluation incl. forecasting, reports, backups) and Faculty/Staff roles. |

---

## 5. Dr. Keno Piad

| Question | Action Taken |
| --- | --- |
| **1. How prove the system actually reduced operational problems, not just perceived ISO/IEC 25010 quality?** | The evaluation was strengthened beyond perception surveys with **objective system metrics**: (a) forecast performance measured against actuals (**MAPE 19.41%** vs. the ≤ 20% requirement) showing reduced stock-out/overstock risk; (b) **before–after comparison** of processing delay (requisition-to-issuance time) and stock discrepancy using the reconciled 48-month ledger; (c) acceptance criteria tables where each operational problem (manual recording, discrepancy, delay, stock-out/overstock) has a measurable indicator. ISO/IEC 25010 ratings are retained only as the user-perception dimension, explicitly positioned as complementary, not primary, evidence. |
| **2. What constitutes "data analytics"? Is predictive forecasting actually in the system? Where is the methodology?** | **[SYSTEM]** Yes — predictive forecasting is a live module. **Data analytics** = the LSTM model on 48 months of issuance per product, rolling 12-month lookback, 3-month ahead forecasts, suggested reorder quantities, and metrics (MAPE/MAE/RMSE). The **methodology** (algorithm, target variable, data window, training, validation/hold-out, evaluation metrics, acceptance threshold) is now detailed in Chapter 2–3 and summarized in the Demand Forecasting screen; the module reports per-product yearly data coverage so the model basis is auditable in the running system. |
| **3. What specific asset-tracking functions are developed and evaluated, and evidence they work?** | **[SYSTEM]** The asset-tracking component now lists and evaluates: asset **registration & assignment (custodian/office)**, **RIS issuance** with expected return date + overdue detection + actual return + item condition, **PTR** custody transfer, **disposal**, **incidents**, and **maintenance** scheduling. Evidence in the running system: 1,320 active assets with assignment records; RIS return/overdue statuses; incidents/maintenance registers; all reflected in Department Reports. |
| **4. How validate that automated recommendations are correct; authoritative basis?** | **[MANUSCRIPT]** A **rules-validation matrix** was added: each encoded rule (stock sufficiency check before approval, reorder alerts at/below reorder level, budget/quantity guardrails) is mapped to the **authoritative internal source** — the institution's procurement/Property & Supplies Office guidelines — and validated by (a) **expert review** by the office/procurement head and (b) **test-case validation** (edge cases: insufficient stock, zero stock, quantity limits) with expected vs. actual system behavior recorded. |
| **5. Basis for 50 respondents and qualification to judge all ISO/IEC 25010 characteristics?** | **[MANUSCRIPT]** Justification added: the 50 respondents are a **stratified proportionate sample of the four user groups** (purchasing personnel, faculty, non-teaching staff, IT experts) who directly interact with the system; IT experts/purchasing personnel are designated evaluators for the **technical characteristics** (security, maintainability, performance efficiency, compatibility), while faculty and non-teaching staff evaluate the **usability-adjacent** characteristics they are qualified for — matching ISO/IEC 25010 evaluator-fit guidance rather than rating all characteristics by all groups. |

---

*Prepared for the final defense documentation. System assertions are verified against the live deployment (September 2026).*