(() => {
  'use strict';

  const config = window.efsQuizConfig || {};
  const text = config.i18n || {};
  const roots = Array.from(document.querySelectorAll('[data-efs-quiz]'));
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
    const body = root.querySelector('[data-efs-quiz-body]');
    const scoreEl = root.querySelector('[data-efs-quiz-score]');
    const levelEl = root.querySelector('[data-efs-quiz-level]');

    const sessionLength = Number(config.session) > 0 ? Number(config.session) : 20;

    let asked = 0;
    let right = 0;
    let token = '';
    let locked = false;

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
      score.textContent = (text.doneBody || 'You answered %1$s of %2$s correctly.')
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
      message(text.loading || 'Loading…');

      let round;
      try {
        round = await post('efs_quiz_round', { level: levelEl ? levelEl.value : (config.level || '') });
      } catch (error) {
        message(error.message);
        return;
      }

      token = round.token;
      body.replaceChildren();

      const prompt = document.createElement('p');
      prompt.className = 'efs-quiz__word';
      prompt.textContent = round.word;
      body.append(prompt);

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

      const list = document.createElement('div');
      list.className = 'efs-quiz__options';

      round.options.forEach((option) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'efs-quiz__option';
        button.textContent = option.text;
        button.addEventListener('click', () => answer(option.id, button, list));
        list.append(button);
      });

      body.append(list);
    };

    const answer = async (choice, button, list) => {
      // One answer per round; the server also enforces this by consuming the
      // round, but stopping here avoids a pointless request.
      if (locked) return;
      locked = true;

      let result;
      try {
        result = await post('efs_quiz_answer', { token, choice: String(choice) });
      } catch (error) {
        message(error.message);
        return;
      }

      asked += 1;
      if (result.correct) right += 1;
      // Shared English Finders signal (EFS 1.13.1): English Finders Account invites guests to save their progress.
      if (result.correct) document.dispatchEvent(new CustomEvent('ef:progress', { detail: { source: 'efs-vocabulary-quiz', kind: 'correct' } }));
      showScore();

      Array.from(list.children).forEach((el, index) => {
        el.disabled = true;
        if (index === result.answer) el.classList.add('is-correct');
        else if (el === button) el.classList.add('is-wrong');
      });

      const verdict = document.createElement('p');
      verdict.className = result.correct ? 'efs-quiz__verdict is-correct' : 'efs-quiz__verdict is-wrong';
      verdict.textContent = result.correct ? (text.correct || 'Correct') : (text.wrong || 'Not quite');
      body.append(verdict);

      // A session has a fixed length so there is a point of completion; an
      // endless stream of questions gives no sense of progress.
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
