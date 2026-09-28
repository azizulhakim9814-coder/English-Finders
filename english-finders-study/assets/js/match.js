(() => {
  'use strict';

  const config = window.efsMatchConfig || {};
  const text = config.i18n || {};
  const roots = Array.from(document.querySelectorAll('[data-efs-match]'));
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
    if (!json || !json.success) throw new Error(json?.data?.message || text.error || 'Error');
    return json.data;
  };

  roots.forEach((root) => {
    const body = root.querySelector('[data-efs-match-body]');
    const scoreEl = root.querySelector('[data-efs-match-score]');
    const levelEl = root.querySelector('[data-efs-match-level]');
    const sessionLength = Number(config.session) > 0 ? Number(config.session) : 5;

    let round = 0;
    let right = 0;
    let total = 0;
    let token = '';
    let selectedWord = null;
    let pairs = new Map();

    const showScore = () => {
      if (!round) { scoreEl.textContent = ''; return; }
      const progress = `${text.round || 'Round'} ${Math.min(round, sessionLength)} / ${sessionLength}`;
      scoreEl.textContent = total ? `${progress} · ${text.score || 'Score'}: ${right} / ${total}` : progress;
    };

    const message = (content) => {
      body.replaceChildren();
      const p = document.createElement('p');
      p.className = 'efs-quiz__message';
      p.textContent = content;
      body.append(p);
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
      score.textContent = (text.doneBody || 'You matched %1$s of %2$s correctly.')
        .replace('%1$s', String(right))
        .replace('%2$s', String(total));

      const again = document.createElement('button');
      again.type = 'button';
      again.className = 'efs-quiz__next';
      again.textContent = text.restart || 'Start a new session';
      again.addEventListener('click', () => { round = 0; right = 0; total = 0; showScore(); nextRound(); });

      wrap.append(title, score, again);
      body.append(wrap);
      again.focus({ preventScroll: true });
    };

    const clearSelection = () => {
      selectedWord = null;
      body.querySelectorAll('.efs-match__word').forEach((el) => el.classList.remove('is-selected'));
    };

    const nextRound = async () => {
      pairs = new Map();
      selectedWord = null;
      message(text.loading || 'Loading…');

      let data;
      try {
        data = await post('efs_match_round', { level: levelEl ? levelEl.value : (config.level || '') });
      } catch (error) {
        message(error.message);
        return;
      }

      token = data.token;
      round += 1;
      showScore();

      body.replaceChildren();

      const hint = document.createElement('p');
      hint.className = 'efs-quiz__message';
      hint.textContent = text.prompt || 'Select a word, then its meaning.';
      body.append(hint);

      const grid = document.createElement('div');
      grid.className = 'efs-match__grid';

      const wordCol = document.createElement('div');
      wordCol.className = 'efs-match__col';
      const defCol = document.createElement('div');
      defCol.className = 'efs-match__col';

      data.words.forEach((word) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'efs-quiz__option efs-match__word';
        button.dataset.wordId = String(word.id);
        button.textContent = word.text;
        button.addEventListener('click', () => {
          if (button.disabled) return;
          clearSelection();
          selectedWord = button;
          button.classList.add('is-selected');
        });
        wordCol.append(button);
      });

      data.definitions.forEach((definition) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'efs-quiz__option efs-match__def';
        button.dataset.defId = String(definition.id);
        button.textContent = definition.text;
        button.addEventListener('click', () => {
          if (button.disabled || !selectedWord) return;

          const wordId = Number(selectedWord.dataset.wordId);
          pairs.set(wordId, Number(button.dataset.defId));

          // Both halves lock once paired, so a pairing cannot be silently
          // replaced without the learner noticing.
          selectedWord.disabled = true;
          selectedWord.classList.add('is-paired');
          selectedWord.classList.remove('is-selected');
          button.disabled = true;
          button.classList.add('is-paired');

          selectedWord = null;
          if (pairs.size === data.words.length) checkButton.disabled = false;
        });
        defCol.append(button);
      });

      grid.append(wordCol, defCol);
      body.append(grid);

      const checkButton = document.createElement('button');
      checkButton.type = 'button';
      checkButton.className = 'efs-quiz__next';
      checkButton.textContent = text.check || 'Check answers';
      checkButton.disabled = true;
      checkButton.addEventListener('click', () => check(checkButton, data));
      body.append(checkButton);
    };

    const check = async (button, data) => {
      button.disabled = true;

      const encoded = Array.from(pairs.entries()).map(([w, d]) => `${w}:${d}`).join(',');

      let result;
      try {
        result = await post('efs_match_check', { token, pairs: encoded });
      } catch (error) {
        message(error.message);
        return;
      }

      right += result.correct;
      // Shared English Finders signal (EFS 1.13.1): English Finders Account invites guests to save their progress.
      if (result.correct) document.dispatchEvent(new CustomEvent('ef:progress', { detail: { source: 'efs-definition-match', kind: 'correct' } }));
      total += result.total;
      showScore();

      const correctIds = new Set(result.results.filter((r) => r.correct).map((r) => r.word));
      body.querySelectorAll('.efs-match__word').forEach((el) => {
        const id = Number(el.dataset.wordId);
        el.classList.add(correctIds.has(id) ? 'is-correct' : 'is-wrong');
      });

      button.remove();

      const verdict = document.createElement('p');
      verdict.className = result.correct === result.total ? 'efs-quiz__verdict is-correct' : 'efs-quiz__verdict is-wrong';
      verdict.textContent = `${result.correct} / ${result.total}`;
      body.append(verdict);

      const next = document.createElement('button');
      next.type = 'button';
      next.className = 'efs-quiz__next';
      next.textContent = round >= sessionLength ? (text.done || 'Session complete') : (text.next || 'Next round');
      next.addEventListener('click', () => (round >= sessionLength ? finish() : nextRound()));
      body.append(next);
      next.focus({ preventScroll: true });
    };

    levelEl?.addEventListener('change', () => {
      round = 0; right = 0; total = 0;
      showScore();
      nextRound();
    });

    nextRound();
  });
})();
