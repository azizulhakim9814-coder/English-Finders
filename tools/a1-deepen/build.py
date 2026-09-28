"""Build deepened A1 lesson HTML from structured lesson data (modules m*.py)."""
import hashlib, html, importlib, json, os, re, sys

BTN = ('<p style="margin:10px 0 16px 0;"><a href="{url}" target="_blank" rel="noopener" '
       'style="display:inline-block;margin:4px 8px 4px 0;padding:10px 20px;background-color:#2563eb;'
       'color:#ffffff;text-decoration:none;border-radius:6px;font-weight:600;font-size:15px;">{label} →</a></p>')
BASE = 'https://englishfinders.com'
VALID = {'/grammar-quiz/', '/vocabulary-quiz/', '/spelling-quiz/', '/match-the-definition/', '/error-correction/',
         '/sentence-builder/', '/reading-quiz/', '/pronunciation-practice/', '/english-dictionary-search/',
         '/word-ladder/', '/word-chain-challenge/', '/hangman-reimagined/', '/word-practice/', '/learn/a1/',
         '/word-finder/a1-words/', '/vocabulary/', '/grammar/', '/english-level-test/', '/speaking/', '/writing/',
         '/reading/', '/listening/', '/daily-unscramble/'}


def lis(items, tag='ul'):
    return '<' + tag + '>\n' + '\n'.join('<li>' + i + '</li>' for i in items) + '\n</' + tag + '>'


def answers(items):
    return ('<details><summary><strong>Show the answers</strong></summary>\n'
            + lis(items, 'ol') + '\n</details>')


def connect(c):
    out = []
    for text, path, label in c:
        assert path in VALID, path
        out.append('<p>' + text + '</p>\n' + BTN.format(url=BASE + path, label=label))
    return '<h2>Connect</h2>\n' + '\n'.join(out)


def watch(w):
    rows = ['<li>✗ <em>' + a + '</em> → ✓ <strong>' + b + '</strong>' + (' (' + why + ')' if why else '') + '</li>'
            for a, b, why in w]
    return '<h2>Watch Out</h2>\n<ul>\n' + '\n'.join(rows) + '\n</ul>'


def lesson(L):
    p = []
    p.append('<h2>Hook</h2>\n<p>' + L['hook'] + '</p>')
    p.append('<h2>Notice</h2>\n' + ('<p>' + L['notice_intro'] + '</p>\n' if L.get('notice_intro') else '') + lis(L['notice'])
             + ('\n<p>' + L['notice_q'] + '</p>' if L.get('notice_q') else ''))
    p.append('<h2>Rule</h2>\n' + L['rule'])
    if L.get('watch'):
        p.append(watch(L['watch']))
    p.append('<h2>Practice</h2>\n' + ('<p>' + L['practice_intro'] + '</p>\n' if L.get('practice_intro') else '')
             + lis([q for q, _ in L['practice']], 'ol') + '\n' + answers([a for _, a in L['practice']]))
    p.append('<h2>Your Turn</h2>\n<p>' + L['your_turn'] + '</p>')
    p.append(connect(L['connect']))
    p.append('<p><em>CEFR A1 — ' + L['cefr'] + '</em></p>')
    return '\n\n'.join(p) + '\n'


def cando(L):
    p = ['<h2>Your Task</h2>\n<p>' + L['task'] + '</p>']
    p.append('<h2>Before You Start</h2>\n' + lis(L['steps'], 'ol'))
    p.append('<h2>Useful Language</h2>\n' + L['language'])
    p.append('<h2>Model Answer</h2>\n<p>Read this example first. Then write your own; don\'t copy it.</p>\n<blockquote>' + L['model'] + '</blockquote>'
             + ('\n<p>' + L['model_note'] + '</p>' if L.get('model_note') else ''))
    p.append('<h2>Check Your Work</h2>\n<p>Tick each box before you finish:</p>\n' + lis(['☐ ' + c for c in L['checklist']]))
    if L.get('extend'):
        p.append('<h2>Go Further</h2>\n<p>' + L['extend'] + '</p>')
    p.append(connect(L['connect']))
    p.append('<p><em>CEFR A1 — ' + L['cefr'] + '</em></p>')
    return '\n\n'.join(p) + '\n'


def worksheet(L):
    p = []
    if L.get('image'):
        p.append(L['image'])
    p.append('<p><em>Print this page, or complete it right here online. The answer key is at the bottom: try every part first.</em></p>')
    for title, intro, items, kind in L['parts']:
        body = ('<p>' + intro + '</p>\n' if intro else '') + (lis(items, 'ol') if items else '')
        p.append('<h2>' + title + '</h2>\n' + body)
    p.append('<hr />\n<h2>Answer Key</h2>\n' + '\n'.join('<p><strong>' + k + ':</strong> ' + v + '</p>' for k, v in L['key']))
    if L.get('connect'):
        p.append(connect(L['connect']))
    p.append('<p><em>CEFR A1 — ' + L['cefr'] + '</em></p>')
    return '\n\n'.join(p) + '\n'


def raw(L):
    return L['html'].strip() + '\n'


KINDS = {'lesson': lesson, 'cando': cando, 'worksheet': worksheet, 'raw': raw}


def build(mod):
    m = importlib.import_module(mod)
    out = {}
    for L in m.LESSONS:
        h = KINDS[L.get('kind', 'lesson')](L)
        assert '\\u' not in h and '∞' not in h, L['id']
        assert h.count('<details>') == h.count('</details>')
        os.makedirs('out', exist_ok=True)
        open('out/%d.html' % L['id'], 'w').write(h)
        text = re.sub(r'<[^>]+>', ' ', h)
        out[L['id']] = (len(html.unescape(re.sub(r'\s+', ' ', text)).strip()), hashlib.sha256(h.encode()).hexdigest())
    return out


if __name__ == '__main__':
    for mod in sys.argv[1:]:
        for k, (n, sha) in build(mod).items():
            print(mod, k, n, sha[:12])
