const jsdom = require("jsdom");
const { JSDOM } = jsdom;

const dom = new JSDOM(`<!DOCTYPE html><html><body></body></html>`);
global.window = dom.window;
global.document = dom.window.document;
global.URL = dom.window.URL;

const renderer = require('../../resources/js/ai-renderer.js');

function test(name, fn) {
    try {
        fn();
        console.log(`[PASS] ${name}`);
    } catch (e) {
        console.error(`[FAIL] ${name}`);
        console.error(e.message);
        process.exitCode = 1;
    }
}

function assert(condition, message) {
    if (!condition) throw new Error(message || "Assertion failed");
}

function assertEqual(actual, expected, message) {
    if (actual !== expected) throw new Error((message || "") + `\nExpected: ${expected}\nActual: ${actual}`);
}

test('isSafeUrl allows http/https and relative', () => {
    assert(renderer.isSafeUrl('http://example.com'), 'Allows http');
    assert(renderer.isSafeUrl('https://example.com'), 'Allows https');
    assert(renderer.isSafeUrl('/api/test'), 'Allows relative path');
    assert(renderer.isSafeUrl('#anchor'), 'Allows anchor');
    
    assert(!renderer.isSafeUrl('javascript:alert(1)'), 'Blocks javascript:');
    assert(!renderer.isSafeUrl('data:text/html,<script>alert(1)</script>'), 'Blocks data:');
    assert(!renderer.isSafeUrl('vbscript:msgbox(1)'), 'Blocks vbscript:');
});

test('renderAIMessage escapes HTML in text_summary (content)', () => {
    const malicious = '<script>alert("xss")</script>';
    const el = renderer.renderAIMessage('assistant', malicious, 'text');
    const bubble = el.querySelector('.ai-bubble');
    
    // textContent strips out tags if they were parsed as HTML. 
    // If it was correctly inserted as a TextNode, innerHTML will have &lt;script&gt;
    assert(bubble.innerHTML.includes('&lt;script&gt;alert("xss")&lt;/script&gt;'), "HTML should be escaped in innerHTML");
    assert(!bubble.querySelector('script'), "Should not contain a parsed script tag");
});

test('renderAIMessage escapes HTML in cards, warnings, and errors', () => {
    const malicious = '<img src=x onerror=alert(1)>';
    const metadata = {
        warnings: [malicious],
        errors: [malicious],
        cards: [{ title: malicious, value: malicious }]
    };
    
    const el = renderer.renderAIMessage('assistant', 'Summary', 'structured', metadata);
    const bubble = el.querySelector('.ai-bubble');
    
    assert(bubble.innerHTML.includes('&lt;img src=x onerror=alert(1)&gt;'), "Card/Warning/Error text should be escaped");
    assert(!bubble.querySelector('img'), "Should not contain a parsed img tag");
});

test('renderAIMessage escapes HTML in tables', () => {
    const malicious = '<iframe src="javascript:alert(1)"></iframe>';
    const metadata = {
        table: {
            columns: [malicious, "Safe Col"],
            rows: [
                [malicious, "Safe Value"],
                { [malicious]: malicious, "Safe Col": "Value 2" }
            ]
        }
    };
    
    const el = renderer.renderAIMessage('assistant', 'Summary', 'structured', metadata);
    const bubble = el.querySelector('.ai-bubble');
    
    assert(bubble.innerHTML.includes('&lt;iframe'), "Table headers and cells should be escaped");
    assert(!bubble.querySelector('iframe'), "Should not contain a parsed iframe tag");
});

test('renderAIMessage strips unsafe links', () => {
    const metadata = {
        links: [
            { label: 'Safe Link', url: 'https://example.com' },
            { label: 'Evil Link', url: 'javascript:alert(1)' },
            { label: '<script>alert("label")</script>', url: '/relative' }
        ]
    };
    
    const el = renderer.renderAIMessage('assistant', 'Summary', 'structured', metadata);
    const links = el.querySelectorAll('a');
    
    assertEqual(links.length, 2, "Should only render 2 safe links");
    assertEqual(links[0].href, 'https://example.com/', "First link is safe");
    assert(links[1].href.endsWith('/relative'), "Second link is safe relative");
    
    assert(links[1].innerHTML.includes('&lt;script&gt;'), "Link label should be escaped");
});

test('renderAIError escapes HTML', () => {
    const malicious = '<script>alert("error")</script>';
    const el = renderer.renderAIError(malicious);
    const errEl = el.querySelector('.ai-error');
    
    assert(errEl.innerHTML.includes('&lt;script&gt;'), "Error message should be escaped");
    assert(!errEl.querySelector('script'), "Should not contain a parsed script tag");
});

console.log("Renderer JS tests complete.");
