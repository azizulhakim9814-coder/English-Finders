/*
 * English Level Test (Phase A4).
 *
 * Thin client: the server holds the session, the correct answers and the
 * scoring rules (LevelTest / LevelTestEngine). This only renders the
 * question it is given, posts the chosen option id with the session token
 * and sequence number, and renders the result. All text is inserted with
 * textContent -- nothing from a response is ever parsed as HTML.
 */
(() => {
  'use strict';

  const config = window.efsLevelTestConfig || {};
  const text = config.i18n || {};
  const roots = Array.from(document.querySelectorAll('[data-efs-level-test]'));
  if (!roots.length || !config.ajaxUrl) return;

  const post = async (action, payload) => {
    const body = new URLSearchParams({ action, ...payload });
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

  const el = (tag, className, content) => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (content !== undefined && content !== null) node.textContent = String(content);
    return node;
  };

  roots.forEach((root) => {
    const body = root.querySelector('[data-efs-level-body]');
    const progressWrap = root.querySelector('[data-efs-level-progress]');
    const progressFill = root.querySelector('[data-efs-level-progress-fill]');
    const progressText = root.querySelector('[data-efs-level-progress-text]');
    const startButton = root.querySelector('[data-efs-level-start]');
    if (!body) return;

    let token = '';
    let locked = false;

    const setProgress = (percent, number) => {
      if (!progressWrap) return;
      progressWrap.hidden = false;
      const clamped = Math.max(0, Math.min(100, Number(percent) || 0));
      progressFill.style.width = `${clamped}%`;
      progressWrap.setAttribute('aria-label', `${text.progress || 'Test progress'}: ${clamped}%`);
      progressText.textContent = number ? (text.question || 'Question %s').replace('%s', String(number)) : '';
    };

    const message = (content, retry) => {
      body.replaceChildren(el('p', 'efs-quiz__message', content));
      if (retry) {
        const again = el('button', 'efs-quiz__next', text.retake || 'Take the test again');
        again.type = 'button';
        again.addEventListener('click', start);
        body.append(again);
      }
    };

    // The blank in a grammar sentence is styled distinctly, the same way the Grammar Quiz does it.
    const renderSentence = (container, prompt) => {
      const parts = String(prompt).split('______');
      parts.forEach((part, index) => {
        if (part) container.append(document.createTextNode(part));
        if (index < parts.length - 1) container.append(el('span', 'efs-quiz__blank', ''));
      });
    };

    const renderQuestion = (question, percent) => {
      locked = false;
      setProgress(percent, question.number);
      body.replaceChildren();

      const meta = el('p', 'efs-quiz__meta');
      meta.append(el('span', 'efs-quiz__tag', question.skillLabel));
      body.append(meta);

      // The element that receives focus for each new question: the question itself, never an option.
      let focusTarget;
      if (question.type === 'reading') {
        body.append(el('p', 'efs-quiz__passage', question.passage));
        focusTarget = el('p', 'efs-quiz__sentence', question.prompt);
        body.append(focusTarget);
      } else if (question.type === 'vocabulary') {
        body.append(el('p', 'efs-level__vocab-prompt', text.vocabPrompt || 'What does this word mean?'));
        const word = el('p', 'efs-level__word', question.word);
        if (question.pos) word.append(el('span', 'efs-level__pos', question.pos));
        body.append(word);
        focusTarget = word;
      } else {
        const sentence = el('p', 'efs-quiz__sentence');
        renderSentence(sentence, question.prompt);
        body.append(sentence);
        focusTarget = sentence;
      }

      const list = el('div', 'efs-quiz__options');
      question.options.forEach((option) => {
        const button = el('button', 'efs-quiz__option', option.text);
        button.type = 'button';
        button.addEventListener('click', () => answer(question.seq, option.id, button, list));
        list.append(button);
      });
      body.append(list);

      // Focusing the first option made it look pre-selected (seen in a real
      // browser render), which a test must never suggest. Focus the question
      // instead: keyboard users land at the top of the new question and Tab
      // straight into the options.
      focusTarget.setAttribute('tabindex', '-1');
      focusTarget.classList.add('efs-level__focus');
      focusTarget.focus({ preventScroll: true });
    };

    const renderResult = (result) => {
      setProgress(100, 0);
      body.replaceChildren();

      const wrap = el('div', 'efs-level__result');
      wrap.append(el('p', 'efs-quiz__kicker', text.resultKicker || 'Your estimated level'));

      const badge = el('div', 'efs-level__badge');
      badge.append(el('span', 'efs-level__badge-code', result.overall.short));
      badge.append(el('span', 'efs-level__badge-label', result.overall.label));
      wrap.append(badge);
      wrap.append(el('p', 'efs-level__description', result.overall.description));

      wrap.append(el('h3', 'efs-level__skills-title', text.skills || 'By skill'));
      const skills = el('ul', 'efs-level__skills');
      result.skills.forEach((skill) => {
        const row = el('li', 'efs-level__skill');
        row.append(el('span', 'efs-level__skill-name', skill.label));
        const bar = el('span', 'efs-level__scale');
        bar.setAttribute('aria-hidden', 'true');
        // Six segments, A1..C2; index 0 is Pre-A1, so a Pre-A1 skill fills none.
        for (let i = 1; i <= 6; i += 1) {
          bar.append(el('span', i <= skill.index ? 'efs-level__seg is-filled' : 'efs-level__seg'));
        }
        row.append(bar);
        row.append(el('span', 'efs-level__skill-level', skill.short));
        skills.append(row);
      });
      wrap.append(skills);

      wrap.append(el('p', 'efs-level__score', (text.answered || 'You answered %1$s of %2$s questions correctly.')
        .replace('%1$s', String(result.correct))
        .replace('%2$s', String(result.answered))));

      const actions = el('div', 'efs-level__actions');
      if (result.saved && result.loggedIn) {
        wrap.append(el('p', 'efs-level__note', text.saved || 'This result is saved to your account.'));
        const link = el('a', 'efs-quiz__next', text.viewAccount || 'See it in My Level');
        link.href = `${result.accountUrl}#efa-section-level`;
        actions.append(link);
      } else if (result.saved) {
        wrap.append(el('p', 'efs-level__note', text.anonSave || ''));
        const link = el('a', 'efs-quiz__next', text.signUp || 'Create a free account');
        link.href = result.accountUrl;
        actions.append(link);
      } else {
        wrap.append(el('p', 'efs-level__note', text.notSaved || ''));
      }

      if (result.course && result.course.url) {
        const course = el('a', 'efs-level__secondary', (text.course || 'Start the %s course').replace('%s', result.course.level));
        course.href = result.course.url;
        actions.append(course);
      }

      const again = el('button', 'efs-level__secondary', text.retake || 'Take the test again');
      again.type = 'button';
      again.addEventListener('click', start);
      actions.append(again);
      wrap.append(actions);

      wrap.append(el('p', 'efs-level__disclaimer', text.disclaimer || ''));
      body.append(wrap);
      badge.setAttribute('tabindex', '-1');
      badge.focus({ preventScroll: false });
    };

    const answer = async (seq, choice, button, list) => {
      if (locked) return;
      locked = true;
      list.querySelectorAll('button').forEach((b) => { b.disabled = true; });
      button.classList.add('is-chosen');

      let data;
      try {
        data = await post('efs_level_answer', { token, seq: String(seq), choice: String(choice) });
      } catch (error) {
        message(error.message, true);
        return;
      }

      if (data.done) {
        renderResult(data.result);
      } else {
        renderQuestion(data.question, data.progress);
      }
    };

    async function start() {
      token = '';
      message(text.loading || 'Loading…', false);
      let data;
      try {
        data = await post('efs_level_start', {});
      } catch (error) {
        message(error.message, true);
        return;
      }
      token = data.token;
      renderQuestion(data.question, data.progress);
    }

    if (startButton) startButton.addEventListener('click', start);
  });
})();
