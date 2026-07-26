/**
 * Permissive Gherkin stream mode for editing Feature/Scenario *fragments*
 * (steps-only, Background-only, or full documents).
 *
 * Stock @codemirror/legacy-modes gherkin keeps allowSteps=false until it sees
 * Feature: then Scenario: — so Given/When/Then in the Scenario form never color.
 */
export const gherkinFragment = {
    name: 'gherkin',
    startState() {
        return {
            tableHeaderLine: false,
            allowMultilineArgument: false,
            inMultilineString: false,
            inMultilineTable: false,
            inKeywordLine: false,
            allowPlaceholders: true,
        };
    },
    token(stream, state) {
        if (stream.sol()) {
            state.inKeywordLine = false;
            if (state.inMultilineTable) {
                state.tableHeaderLine = false;
                if (!stream.match(/\s*\|/, false)) {
                    state.allowMultilineArgument = false;
                    state.inMultilineTable = false;
                }
            }
        }

        stream.eatSpace();

        if (state.allowMultilineArgument) {
            if (state.inMultilineString) {
                if (stream.match('"""')) {
                    state.inMultilineString = false;
                    state.allowMultilineArgument = false;
                } else {
                    stream.match(/.*/);
                }
                return 'string';
            }

            if (state.inMultilineTable) {
                if (stream.match(/\|\s*/)) {
                    return 'bracket';
                }
                stream.match(/[^|]*/);
                return state.tableHeaderLine ? 'header' : 'string';
            }

            if (stream.match('"""')) {
                state.inMultilineString = true;
                return 'string';
            }
            if (stream.match('|')) {
                state.inMultilineTable = true;
                state.tableHeaderLine = true;
                return 'bracket';
            }
        }

        // Comments (# …)
        if (stream.match(/#.*/)) {
            return 'comment';
        }

        // @tags
        if (!state.inKeywordLine && stream.match(/@\S+/)) {
            return 'tag';
        }

        // Structural keywords (English-first; enough for BAssist editors)
        if (
            !state.inKeywordLine
            && stream.match(
                /(?:Feature|Background|Scenario Outline|Scenario|Examples)\s*:/i
            )
        ) {
            state.inKeywordLine = true;
            state.allowPlaceholders = true;
            if (/^examples/i.test(stream.current())) {
                state.allowMultilineArgument = true;
            }
            return 'keyword';
        }

        // Step keywords (must match keyword + trailing space, or lone *)
        if (
            !state.inKeywordLine
            && stream.match(/(?:Given|When|Then|And|But)\b|\*/i)
        ) {
            state.inKeywordLine = true;
            state.allowPlaceholders = true;
            state.allowMultilineArgument = true;
            return 'keyword';
        }

        // Table row start outside multiline mode
        if (stream.match('|')) {
            state.allowMultilineArgument = true;
            state.inMultilineTable = true;
            state.tableHeaderLine = true;
            return 'bracket';
        }

        // Doc string start
        if (stream.match('"""')) {
            state.allowMultilineArgument = true;
            state.inMultilineString = true;
            return 'string';
        }

        // Inline strings
        if (stream.match(/"[^"]*"?/)) {
            return 'string';
        }

        // Placeholders <col>
        if (state.allowPlaceholders && stream.match(/<[^>]*>?/)) {
            return 'variable';
        }

        stream.next();
        stream.eatWhile(/[^@"<#|]/);
        return null;
    },
};
