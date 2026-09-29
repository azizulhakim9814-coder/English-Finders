(() => {
  'use strict';

  const config = window.efsSpellingConfig || {};
  const text = config.i18n || {};
  const roots = Array.from(document.querySelectorAll('[data-efs-spelling]'));
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
    const body = root.querySelector('[data-efs-spelling-body]');
    const scoreEl = root.querySelector('[data-efs-spelling-score]');
    const levelEl = root.querySelector('[data-efs-spelling-level]');

    const sessionLength = Number(config.session) > 0 ? Number(config.session) : 15;

    let asked = 0;
    let right = 0;
    let token = '';
    let locked = false;
    let player = null;

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
      score.textContent = (text.doneBody || 'You spelled %1$s of %2$s correctly.')
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

    const nextRound = async () => {
      locked = false;
      player = null;
      message(text.loading || 'Finding a word…');

      let round;
      try {
        round = await post('efs_spelling_round', { level: levelEl ? levelEl.value : (config.level || '') });
      } catch (error) {
        message(error.message);
        return;
      }

      token = round.token;
      body.replaceChildren();

      player = new Audio(round.audioUrl);

      const playRow = document.createElement('div');
      playRow.className = 'efs-spelling__play-row';

      const playButton = document.createElement('button');
      playButton.type = 'button';
      playButton.className = 'efs-spelling__play';
      playButton.textContent = text.listen || 'Listen';
      playButton.addEventListener('click', () => {
        playButton.textContent = text.replay || 'Play again';
        player.currentTime = 0;
        player.play().catch(() => {});
      });
      playRow.append(playButton);

      if (round.length) {
        const hint = document.createElement('span');
        hint.className = 'efs-spelling__hint';
        hint.textContent = (text.letters || '%d letters').replace('%d', String(round.length));
        playRow.append(hint);
      }

      body.append(playRow);

      const tags = [round.pos, round.level].filter(Boolean);
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

      const form = document.createElement('form');
      form.className = 'efs-spelling__form';
      form.noValidate = true;

      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'efs-spelling__input';
      input.placeholder = text.placeholder || 'Type what you hear';
      input.autocomplete = 'off';
      input.autocapitalize = 'off';
      input.spellcheck = false;
      form.append(input);

      const submit = document.createElement('button');
      submit.type = 'submit';
      submit.className = 'efs-quiz__next';
      submit.textContent = text.submit || 'Check';
      form.append(submit);

      form.addEventListener('submit', (event) => {
        event.preventDefault();
        answer(input.value, input);
      });

      body.append(form);

      // Autoplay is blocked by most browsers until a user gesture happens
      // somewhere on the page, so the round starts silent with a visible
      // Listen button rather than a play() call that would usually fail.
      input.focus({ preventScroll: true });
    };

    const answer = async (given, input) => {
      // One answer per round; the server also enforces this by consuming the
      // round, but stopping here avoids a pointless request.
      if (locked) return;
      locked = true;

      let result;
      try {
        result = await post('efs_spelling_answer', { token, answer: given });
      } catch (error) {
        message(error.message);
        return;
      }

      asked += 1;
      if (result.correct) right += 1;
      // Shared English Finders signal (EFS 1.13.1): English Finders Account invites guests to save their progress.
      if (result.correct) document.dispatchEvent(new CustomEvent('ef:progress', { detail: { source: 'efs-spelling-quiz', kind: 'correct' } }));
      showScore();

      input.disabled = true;
      input.classList.add(result.correct ? 'is-correct' : 'is-wrong');
      const submitButton = input.closest('form')?.querySelector('button[type="submit"]');
      if (submitButton) submitButton.disabled = true;

      const verdict = document.createElement('p');
      verdict.className = result.correct ? 'efs-quiz__verdict is-correct' : 'efs-quiz__verdict is-wrong';
      verdict.textContent = result.correct ? (text.correct || 'Correct') : (text.wrong || 'Not quite');
      body.append(verdict);

      if (!result.correct) {
        const reveal = document.createElement('p');
        reveal.className = 'efs-quiz__explain';
        reveal.textContent = `${text.wasLabel || 'The word was:'} ${result.answer}`;
        body.append(reveal);
      }

      if (asked >= sessionLength) {
        const done = document.createElement('button');
        done.type = 'button';
        done.className = 'efs-quiz__next';
        done.textContent = text.done || 'Session complete';
        done.addEventListener('click', finish);
        body.append(done);
        done.focus({ preventScroll: true });
        return;
      }

      const next = document.createElement('button');
      next.type = 'button';
      next.className = 'efs-quiz__next';
      next.textContent = text.next || 'Next word';
      next.addEventListener('click', nextRound);
      body.append(next);
      next.focus({ preventScroll: true });
    };

    // Changing level starts a fresh session: a score mixing A1 and C1 answers
    // would not mean anything.
    levelEl?.addEventListener('change', () => {
      asked = 0;
      right = 0;
      showScore();
      nextRound();
    });

    nextRound();
  });
})();
