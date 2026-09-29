(() => {
  'use strict';

  const config = window.efsSentenceConfig || {};
  const text = config.i18n || {};
  const roots = Array.from(document.querySelectorAll('[data-efs-sentence]'));
  if (!roots.length) return;

  const post = async (action, payload) => {
    const body = new URLSearchParams({ action, nonce: config.nonce, ...payload });
    const response = await fetch(config.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body,
    });
    const json = await response.json().catch(() => ({}));
    if (!json || !json.success) {
      throw new Error(json?.data?.message || text.error || 'Error');
    }
    return json.data;
  };

  roots.forEach((root) => {
    const body = root.querySelector('[data-efs-sentence-body]');
    const scoreEl = root.querySelector('[data-efs-sentence-score]');
    const levelEl = root.querySelector('[data-efs-sentence-level]');

    const sessionLength = Number(config.session) > 0 ? Number(config.session) : 10;
    // Ids this browser has already been served, per level, kept across
    // sessions and visits (1.16.0), so a returning learner meets unseen
    // items first. Once the whole pool has been seen, the server starts a
    // new shuffled cycle and says so with `cycled`. Storage can be missing
    // or blocked (private windows), so every access is guarded and the tool
    // still works, just without memory between visits.
    const storeKey = 'efs_seen_sentence';
    const maxSeen = 400;
    const levelKey = () => (levelEl ? levelEl.value : (config.level || '')) || 'any';
    const readStore = () => {
      try {
        const all = JSON.parse(window.localStorage.getItem(storeKey) || '{}');
        return all && typeof all === 'object' && !Array.isArray(all) ? all : {};
      } catch (error) {
        return {};
      }
    };
    const loadSeen = () => {
      const ids = readStore()[levelKey()];
      return Array.isArray(ids) ? ids.filter((id) => typeof id === 'string').slice(-maxSeen) : [];
    };
    const saveSeen = () => {
      try {
        const all = readStore();
        all[levelKey()] = seen.slice(-maxSeen);
        window.localStorage.setItem(storeKey, JSON.stringify(all));
      } catch (error) {
        // Nothing to do: the current session keeps its own list in memory.
      }
    };
    const seen = loadSeen();

    let asked = 0;
    let right = 0;
    let token = '';
    let locked = false;
    let tokens = [];
    let placed = [];

    const showScore = () => {
      if (!asked) { scoreEl.textContent = ''; return; }
      const progress = `${text.question || 'Question'} ${Math.min(asked, sessionLength)} / ${sessionLength}`;
      scoreEl.textContent = `${progress} · ${text.score || 'Score'}: ${right} / ${asked}`;
    };

    const finish = () => {
      body.replaceChildren();

      const wrap = document.createElement('div');
      wrap.className = 'efs-quiz__done';

      const title = document.createElement('p');
      title.className = 'efs-quiz__done-title';
      title.textContent = text.done || 'Session complete';

      const score = document.createElement('p');
      score.className = 'efs-quiz__done-score';
      score.textContent = (text.doneBody || 'You built %1$s of %2$s sentences correctly.')
        .replace('%1$s', String(right))
        .replace('%2$s', String(asked));

      const again = document.createElement('button');
      again.type = 'button';
      again.className = 'efs-quiz__next';
      again.textContent = text.restart || 'Start a new session';
      again.addEventListener('click', () => {
        asked = 0;
        right = 0;
        showScore();
        nextRound();
      });

      wrap.append(title, score, again);
      body.append(wrap);
      again.focus({ preventScroll: true });
    };

    const message = (content) => {
      body.replaceChildren();
      const p = document.createElement('p');
      p.className = 'efs-quiz__message';
      p.textContent = content;
      body.append(p);
    };

    // Re-renders both rows from `placed` — simpler and less error-prone than
    // moving DOM nodes between two containers by hand.
    const renderBoard = (bankRow, builtRow, checkButton) => {
      bankRow.replaceChildren();
      builtRow.replaceChildren();

      tokens.forEach((wordToken) => {
        if (placed.includes(wordToken.id)) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'efs-quiz__option efs-sentence__token';
        button.textContent = wordToken.text;
        button.addEventListener('click', () => {
          if (locked) return;
          placed.push(wordToken.id);
          renderBoard(bankRow, builtRow, checkButton);
        });
        bankRow.append(button);
      });

      if (!placed.length) {
        const hint = document.createElement('p');
        hint.className = 'efs-sentence__placeholder';
        hint.textContent = text.prompt || 'Tap the words in the correct order.';
        builtRow.append(hint);
      }

      placed.forEach((id) => {
        const wordToken = tokens.find((t) => t.id === id);
        if (!wordToken) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'efs-quiz__option efs-sentence__token is-placed';
        button.textContent = wordToken.text;
        button.addEventListener('click', () => {
          if (locked) return;
          placed = placed.filter((placedId) => placedId !== id);
          renderBoard(bankRow, builtRow, checkButton);
        });
        builtRow.append(button);
      });

      checkButton.disabled = locked || placed.length !== tokens.length;
    };

    const nextRound = async () => {
      locked = false;
      message(text.loading || 'Loading…');

      let round;
      try {
        round = await post('efs_sentence_round', {
          level: levelEl ? levelEl.value : (config.level || ''),
          exclude: seen.join(','),
        });
      } catch (error) {
        message(error.message);
        return;
      }

      token = round.token;
      tokens = round.tokens;
      placed = [];
      if (round.cycled) seen.length = 0;
      if (round.id) seen.push(round.id);
      saveSeen();

      body.replaceChildren();

      const tags = [round.topic, round.level].filter(Boolean);
      if (tags.length) {
        const meta = document.createElement('p');
        meta.className = 'efs-quiz__meta';
        tags.forEach((label) => {
          const tag = document.createElement('span');
          tag.className = 'efs-quiz__tag';
          tag.textContent = label;
          meta.append(tag);
        });
        body.append(meta);
      }

      const builtRow = document.createElement('div');
      builtRow.className = 'efs-sentence__built';

      const bankRow = document.createElement('div');
      bankRow.className = 'efs-sentence__bank';

      const actions = document.createElement('div');
      actions.className = 'efs-sentence__actions';

      const resetButton = document.createElement('button');
      resetButton.type = 'button';
      resetButton.className = 'efs-sentence__reset';
      resetButton.textContent = text.reset || 'Reset';
      resetButton.addEventListener('click', () => {
        if (locked) return;
        placed = [];
        renderBoard(bankRow, builtRow, checkButton);
      });

      const checkButton = document.createElement('button');
      checkButton.type = 'button';
      checkButton.className = 'efs-quiz__next';
      checkButton.textContent = text.submit || 'Check';
      checkButton.addEventListener('click', () => check());

      actions.append(resetButton, checkButton);
      body.append(builtRow, bankRow, actions);

      renderBoard(bankRow, builtRow, checkButton);
    };

    const check = async () => {
      if (locked) return;
      locked = true;

      let result;
      try {
        result = await post('efs_sentence_answer', { token, order: placed.join(',') });
      } catch (error) {
        message(error.message);
        return;
      }

      asked += 1;
      if (result.correct) right += 1;
      // Shared English Finders signal (EFS 1.13.1): English Finders Account invites guests to save their progress.
      if (result.correct) document.dispatchEvent(new CustomEvent('ef:progress', { detail: { source: 'efs-sentence-builder', kind: 'correct' } }));
      showScore();

      body.querySelectorAll('.efs-sentence__token, .efs-sentence__reset, .efs-quiz__next').forEach((el) => {
        el.disabled = true;
      });

      const verdict = document.createElement('p');
      verdict.className = result.correct ? 'efs-quiz__verdict is-correct' : 'efs-quiz__verdict is-wrong';
      verdict.textContent = result.correct ? (text.correct || 'Correct') : (text.wrong || 'Not quite');
      body.append(verdict);

      if (!result.correct) {
        const answer = document.createElement('p');
        answer.className = 'efs-quiz__explain';
        answer.textContent = `${text.wasLabel || 'The correct sentence:'} ${result.sentence}`;
        body.append(answer);
      }

      if (result.explanation) {
        const explain = document.createElement('p');
        explain.className = 'efs-quiz__explain';
        explain.textContent = result.explanation;
        body.append(explain);
      }

      const next = document.createElement('button');
      next.type = 'button';
      next.className = 'efs-quiz__next';
      next.textContent = asked >= sessionLength ? (text.done || 'Session complete') : (text.next || 'Next sentence');
      next.addEventListener('click', () => (asked >= sessionLength ? finish() : nextRound()));
      body.append(next);
      next.focus({ preventScroll: true });
    };

    // Changing level starts a fresh session: a score mixing A1 and C1 answers
    // would not mean anything.
    levelEl?.addEventListener('change', () => {
      asked = 0;
      right = 0;
      seen.splice(0, seen.length, ...loadSeen());
      showScore();
      nextRound();
    });

    nextRound();
  });
})();
