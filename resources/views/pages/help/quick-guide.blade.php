{{--
    Demo Quick Guide — teaching wizard only.
    Hardcoded sample cards. Does not create records.
--}}
<x-modal-content :title="$title" size="full">
    <div class="quick-guide" data-quick-guide>
        <nav class="quick-guide__trail" data-qg-trail aria-label="{{ __('ui.quick_guide') }}">
            <ol class="quick-guide__trail-list">
                <li class="quick-guide__trail-item is-visible is-current" data-qg-trail-item="0" aria-current="step">
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_project') }}</span>
                </li>
                <li class="quick-guide__trail-item" data-qg-trail-item="1" hidden>
                    <span class="quick-guide__trail-connector" aria-hidden="true">→</span>
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_bn') }}</span>
                </li>
                <li class="quick-guide__trail-item" data-qg-trail-item="2" hidden>
                    <span class="quick-guide__trail-connector" aria-hidden="true">→</span>
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_bo') }}</span>
                </li>
                <li class="quick-guide__trail-item" data-qg-trail-item="3" hidden>
                    <span class="quick-guide__trail-connector" aria-hidden="true">→</span>
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_stakeholder') }}</span>
                </li>
                <li class="quick-guide__trail-item" data-qg-trail-item="4" hidden>
                    <span class="quick-guide__trail-connector" aria-hidden="true">→</span>
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_sn') }}</span>
                </li>
                <li class="quick-guide__trail-item" data-qg-trail-item="5" hidden>
                    <span class="quick-guide__trail-connector" aria-hidden="true">→</span>
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_solution') }}</span>
                </li>
                <li class="quick-guide__trail-item" data-qg-trail-item="6" hidden>
                    <span class="quick-guide__trail-connector" aria-hidden="true">→</span>
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_trace') }}</span>
                </li>
                <li class="quick-guide__trail-item" data-qg-trail-item="7" hidden>
                    <span class="quick-guide__trail-connector" aria-hidden="true">→</span>
                    <span class="quick-guide__trail-label">{{ __('ui.quick_guide_trail_cr') }}</span>
                </li>
            </ol>
        </nav>

        <div class="quick-guide__stage">
            <div class="quick-guide__panels">
                {{-- 1. Create Project --}}
                <section class="quick-guide__panel is-active" data-qg-panel="0">
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_project') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_project') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_project') }}</p>
                    <div class="quick-guide__card">
                        <div class="quick-guide__card-kicker">{{ __('ui.quick_guide_sample_project_kicker') }}</div>
                        <div class="quick-guide__card-title">{{ __('ui.quick_guide_sample_project_title') }}</div>
                        <p class="quick-guide__card-body">{{ __('ui.quick_guide_sample_project_body') }}</p>
                    </div>

                    <div class="quick-guide__surround">
                        <h5 class="quick-guide__surround-title">{{ __('ui.quick_guide_surround_title') }}</h5>
                        <div class="quick-guide__surround-grid">
                            <div class="quick-guide__mini">
                                <span class="quick-guide__mini-label">{{ __('ui.quick_guide_surround_risks_label') }}</span>
                                <p class="quick-guide__mini-body">{{ __('ui.quick_guide_surround_risks_example') }}</p>
                            </div>
                            <div class="quick-guide__mini">
                                <span class="quick-guide__mini-label">{{ __('ui.quick_guide_surround_scope_label') }}</span>
                                <p class="quick-guide__mini-body">{{ __('ui.quick_guide_surround_scope_example') }}</p>
                            </div>
                            <div class="quick-guide__mini">
                                <span class="quick-guide__mini-label">{{ __('ui.quick_guide_surround_constraints_label') }}</span>
                                <p class="quick-guide__mini-body">{{ __('ui.quick_guide_surround_constraints_example') }}</p>
                            </div>
                            <div class="quick-guide__mini">
                                <span class="quick-guide__mini-label">{{ __('ui.quick_guide_surround_rules_label') }}</span>
                                <p class="quick-guide__mini-body">{{ __('ui.quick_guide_surround_rules_example') }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 2. Business Need --}}
                <section class="quick-guide__panel" data-qg-panel="1" hidden>
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_bn') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_bn') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_bn') }}</p>
                    <div class="quick-guide__card">
                        <div class="quick-guide__card-kicker">{{ __('ui.quick_guide_sample_bn_kicker') }}</div>
                        <div class="quick-guide__card-title">{{ __('ui.quick_guide_sample_bn_title') }}</div>
                        <p class="quick-guide__card-body">{{ __('ui.quick_guide_sample_bn_body') }}</p>
                    </div>
                </section>

                {{-- 3. Business Objective --}}
                <section class="quick-guide__panel" data-qg-panel="2" hidden>
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_bo') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_bo') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_bo') }}</p>
                    <div class="quick-guide__card">
                        <div class="quick-guide__card-kicker">{{ __('ui.quick_guide_sample_bo_kicker') }}</div>
                        <div class="quick-guide__card-title">{{ __('ui.quick_guide_sample_bo_title') }}</div>
                        <p class="quick-guide__card-body">{{ __('ui.quick_guide_sample_bo_body') }}</p>
                    </div>
                </section>

                {{-- 4. Stakeholders --}}
                <section class="quick-guide__panel" data-qg-panel="3" hidden>
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_stakeholder') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_stakeholder') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_stakeholder') }}</p>
                    <div class="quick-guide__card">
                        <div class="quick-guide__card-kicker">{{ __('ui.quick_guide_sample_stakeholder_kicker') }}</div>
                        <div class="quick-guide__card-title">{{ __('ui.quick_guide_sample_stakeholder_title') }}</div>
                        <p class="quick-guide__card-body">{{ __('ui.quick_guide_sample_stakeholder_body') }}</p>
                    </div>
                </section>

                {{-- 5. Stakeholder Need --}}
                <section class="quick-guide__panel" data-qg-panel="4" hidden>
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_sn') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_sn') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_sn') }}</p>
                    <div class="quick-guide__card">
                        <div class="quick-guide__card-kicker">{{ __('ui.quick_guide_sample_sn_kicker') }}</div>
                        <div class="quick-guide__card-title">{{ __('ui.quick_guide_sample_sn_title') }}</div>
                        <p class="quick-guide__card-body">{{ __('ui.quick_guide_sample_sn_body') }}</p>
                    </div>
                </section>

                {{-- 6. Solution Requirements (FR, NFR, Scenarios) --}}
                <section class="quick-guide__panel" data-qg-panel="5" hidden>
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_solution') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_solution') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_solution') }}</p>
                    <div class="quick-guide__card">
                        <div class="quick-guide__card-kicker">{{ __('ui.quick_guide_sample_solution_kicker') }}</div>
                        <div class="quick-guide__card-title">{{ __('ui.quick_guide_sample_solution_title') }}</div>
                        <p class="quick-guide__card-body">{{ __('ui.quick_guide_sample_solution_body') }}</p>
                    </div>
                </section>

                {{-- 7. Traceability Matrix (spine only — no Change Request node) --}}
                <section class="quick-guide__panel" data-qg-panel="6" hidden>
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_trace') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_trace') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_trace') }}</p>
                    {{-- BA spine only — Stakeholders and surrounding artifacts are not chain nodes --}}
                    <div class="quick-guide__chain" aria-label="{{ __('ui.quick_guide_trail_trace') }}">
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_project') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_project_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_project') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_bn') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_bn_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_bn') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_bo') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_bo_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_bo') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_sn') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_sn_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_sn') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_solution') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_solution_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_solution') }}</span>
                        </div>
                    </div>
                    <div class="quick-guide__takeaway">
                        <p class="quick-guide__takeaway-line">{!! __('ui.quick_guide_matrix_takeaway_coverage') !!}</p>
                        <p class="quick-guide__takeaway-line">{!! __('ui.quick_guide_matrix_takeaway_lineage') !!}</p>
                    </div>
                </section>

                {{-- 8. Change Request (last — sample + matrix copy with red CR between SN and SR) --}}
                <section class="quick-guide__panel" data-qg-panel="7" hidden>
                    <header class="quick-guide__panel-head">
                        <span class="quick-guide__ba">{{ __('ui.quick_guide_ba_cr') }}</span>
                        <h4 class="quick-guide__question">{{ __('ui.quick_guide_q_cr') }}</h4>
                    </header>
                    <p class="quick-guide__hint">{{ __('ui.quick_guide_hint_cr') }}</p>
                    <div class="quick-guide__card">
                        <div class="quick-guide__card-kicker">{{ __('ui.quick_guide_sample_cr_kicker') }}</div>
                        <div class="quick-guide__card-title">{{ __('ui.quick_guide_sample_cr_title') }}</div>
                        <p class="quick-guide__card-body">{{ __('ui.quick_guide_sample_cr_body') }}</p>
                    </div>
                    <p class="quick-guide__hint quick-guide__hint--cr-chain">{{ __('ui.quick_guide_hint_cr_chain') }}</p>
                    {{-- Post-change matrix: same project/BN/BO, updated SN, red CR, new SR driven by the CR --}}
                    <div class="quick-guide__chain" aria-label="{{ __('ui.quick_guide_cr_chain_label') }}">
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_project') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_project_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_project') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_bn') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_bn_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_bn') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_bo') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_bo_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_bo') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node quick-guide__chain-node--changed">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_sn') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_cr_sn_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_cr_sn') }}</span>
                            <span class="quick-guide__chain-detail">{{ __('ui.quick_guide_chain_cr_sn_badge') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node quick-guide__chain-node--cr">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_cr') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_cr_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_cr') }}</span>
                        </div>
                        <div class="quick-guide__chain-link" aria-hidden="true"><span>&gt;</span><span>&lt;</span></div>
                        <div class="quick-guide__chain-node quick-guide__chain-node--changed">
                            <span class="quick-guide__chain-label">{{ __('ui.quick_guide_trail_solution') }}</span>
                            <span class="quick-guide__chain-code">{{ __('ui.quick_guide_chain_cr_solution_code') }}</span>
                            <span class="quick-guide__chain-value">{{ __('ui.quick_guide_chain_cr_solution') }}</span>
                            <span class="quick-guide__chain-detail">{{ __('ui.quick_guide_chain_cr_solution_badge') }}</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="quick-guide__nav">
            <button type="button" class="kt-btn kt-btn-outline gap-1.5" data-qg-prev disabled>
                {{ __('ui.quick_guide_previous') }}
            </button>
            <div class="quick-guide__nav-end">
                <button type="button" class="kt-btn kt-btn-primary gap-1.5" data-qg-next>
                    {{ __('ui.quick_guide_next') }}
                </button>
                <button type="button" class="kt-btn kt-btn-primary hidden" data-qg-done data-kt-modal-dismiss="true">
                    {{ __('ui.quick_guide_done') }}
                </button>
            </div>
        </div>
    </div>
</x-modal-content>

<script>
(function () {
    const root = document.querySelector('#mianModal [data-quick-guide]') || document.querySelector('[data-quick-guide]');
    if (!root || root.dataset.qgReady === '1') {
        return;
    }
    root.dataset.qgReady = '1';

    const panels = Array.from(root.querySelectorAll('[data-qg-panel]'));
    const trailItems = Array.from(root.querySelectorAll('[data-qg-trail-item]'));
    const prevBtns = Array.from(root.querySelectorAll('[data-qg-prev]'));
    const nextBtns = Array.from(root.querySelectorAll('[data-qg-next]'));
    const doneBtn = root.querySelector('[data-qg-done]');

    let index = 0;
    const last = panels.length - 1;
    let cleaned = false;

    function show(i) {
        index = Math.max(0, Math.min(last, i));

        panels.forEach((panel) => {
            const active = Number(panel.getAttribute('data-qg-panel')) === index;
            panel.classList.toggle('is-active', active);
            if (active) {
                panel.removeAttribute('hidden');
                panel.classList.remove('is-entering');
                void panel.offsetWidth;
                panel.classList.add('is-entering');
            } else {
                panel.setAttribute('hidden', '');
                panel.classList.remove('is-entering');
            }
        });

        trailItems.forEach((item) => {
            const n = Number(item.getAttribute('data-qg-trail-item'));
            const reached = n <= index;
            const current = n === index;

            item.classList.toggle('is-visible', reached);
            item.classList.toggle('is-current', current);
            item.classList.toggle('is-past', n < index);

            if (reached) {
                item.removeAttribute('hidden');
            } else {
                item.setAttribute('hidden', '');
            }

            if (current) {
                item.setAttribute('aria-current', 'step');
            } else {
                item.removeAttribute('aria-current');
            }
        });

        const atStart = index === 0;
        const atEnd = index === last;

        prevBtns.forEach((btn) => {
            btn.disabled = atStart;
        });

        nextBtns.forEach((btn) => {
            btn.disabled = atEnd;
            btn.classList.toggle('hidden', atEnd);
        });

        if (doneBtn) {
            doneBtn.classList.toggle('hidden', !atEnd);
        }
    }

    function isTypingTarget(el) {
        if (!el || !(el instanceof Element)) {
            return false;
        }

        const tag = el.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return true;
        }

        if (el.isContentEditable) {
            return true;
        }

        return !!el.closest('input, textarea, select, [contenteditable="true"]');
    }

    function goPrev() {
        if (index > 0) {
            show(index - 1);
        }
    }

    function goNext() {
        if (index < last) {
            show(index + 1);
        }
    }

    function onKeydown(event) {
        if (!root.isConnected) {
            teardown();
            return;
        }

        const modal = document.getElementById('mianModal');
        if (!modal?.classList.contains('open') || !modal.contains(root)) {
            return;
        }

        if (isTypingTarget(event.target)) {
            return;
        }

        if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
            event.preventDefault();
            goPrev();
            return;
        }

        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
            event.preventDefault();
            goNext();
        }
    }

    function teardown() {
        if (cleaned) {
            return;
        }
        cleaned = true;
        document.removeEventListener('keydown', onKeydown);
        if (observer) {
            observer.disconnect();
        }
    }

    prevBtns.forEach((btn) => btn.addEventListener('click', goPrev));
    nextBtns.forEach((btn) => btn.addEventListener('click', goNext));

    trailItems.forEach((item) => {
        item.addEventListener('click', () => {
            const n = Number(item.getAttribute('data-qg-trail-item'));
            if (n <= index) {
                show(n);
            }
        });
    });

    document.addEventListener('keydown', onKeydown);

    const observeTarget = root.closest('[data-modal-container]') || root.parentElement;
    const observer = observeTarget
        ? new MutationObserver(() => {
            if (!root.isConnected) {
                teardown();
            }
        })
        : null;

    if (observer && observeTarget) {
        observer.observe(observeTarget, { childList: true });
    }

    show(0);
})();
</script>
