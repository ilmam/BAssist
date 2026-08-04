<style>
    tr.is-critical-risk-row td {
        background-color: color-mix(in srgb, #f04438 10%, transparent);
    }

    tr.is-critical-risk-row td:first-child {
        box-shadow: inset 3px 0 0 #f04438;
    }

    .risk-list-score {
        display: inline-block;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font: 600 11px/1.3 system-ui, sans-serif;
        white-space: nowrap;
        background: #eef2f6;
        color: #4b5675;
    }

    .risk-list-score--high {
        background: #fff3e0;
        color: #b54708;
    }

    .risk-list-score--critical {
        background: #fee4e2;
        color: #b42318;
    }
</style>
