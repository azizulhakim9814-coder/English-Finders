(() => {
  'use strict';

  const config = window.efsWritingConfig || {};
  const text = config.i18n || {};
  const root = document.querySelector('[data-efs-writing]');
  if (!root) return;

  const levelEl = root.querySelector('[data-efs-writing-level]');
  const taskEl = root.querySelector('[data-efs-writing-task]');
  const promptEl = root.querySelector('[data-efs-writing-prompt]');
  const textEl = root.querySelector('[data-efs-writing-text]');
  const countEl = root.querySelector('[data-efs-writing-count]');
  const actionsEl = root.querySelector('[data-efs-writing-actions]');
  const checkEl = root.querySelector('[data-efs-writing-check]');
  const statusEl = root.querySelector('[data-efs-writing-status]');
  const resultEl = root.querySelector('[data-efs-writing-result]');

  const tasks = config.tasks || {};
  const maxWords = Number(config.maxWords) || 400;
  const fmt = (template, ...values) => values.reduce(
    (out, value, i) => out.replace(`%${i + 1}$s`, value).replace('%s', value),
    template || ''
  );

  let state = { signedIn: false, enabled: false };
  let busy = false;

  // Drafts live in this browser only (per task), so a guest's text survives
  // signing in. Storage can be missing or blocked; the tool works without it.
  const draftKey = 'efs_writing_drafts';
  const readDrafts = () => {
    try {
      const all = JSON.parse(window.localStorage.getItem(draftKey) || '{}');
      return all && typeof all === 'object' && !Array.isArray(all) ? all : {};
    } catch (error) {
      return {};
    }
  };
  const saveDraft = () => {
    try {
      const all = readDrafts();
      if (textEl.value.trim()) all[taskEl.value] = textEl.value.slice(0, 4000);
      else delete all[taskEl.value];
      window.localStorage.setItem(draftKey, JSON.stringify(all));
      window.localStorage.setItem('efs_writing_task', taskEl.value);
    } catch (error) {
      // Nothing to do.
    }
  };
  const savedTask = () => {
    try { return window.localStorage.getItem('efs_writing_task') || ''; } catch (error) { return ''; }
  };

  const countWords = (value) => (value.match(/[\p{L}\p{N}]+(?:['’-][\p{L}\p{N}]+)*/gu) || []).length;

  const currentTask = () => (tasks[levelEl.value] || []).find((t) => t.id === taskEl.value) || null;

  // Mirrors WritingFeedback::word_limits() on the server.
  const limits = (task) => [Math.max(10, Math.floor(task.min * 0.6)), Math.min(maxWords, task.max + 60)];

  const fillTasks = (preferred) => {
    const list = tasks[levelEl.value] || [];
    taskEl.replaceChildren(...list.map((t) => {
      const option = document.createElement('option');
      option.value = t.id;
      option.textContent = t.title;
      return option;
    }));
    if (preferred && list.some((t) => t.id === preferred)) taskEl.value = preferred;
    showTask();
  };

  const showTask = () => {
    const task = currentTask();
    promptEl.textContent = task ? task.task : '';
    textEl.value = readDrafts()[taskEl.value] || '';
    resultEl.hidden = true;
    resultEl.replaceChildren();
    update();
  };

  const update = () => {
    const task = currentTask();
    const words = countWords(textEl.value);
    let note = fmt(text.words, String(words));
    let ok = false;
    if (task) {
      const [min, max] = limits(task);
      note += ` · ${fmt(text.target, String(task.min), String(task.max))}`;
      if (words > max) note += ` ${fmt(text.tooLong, String(max))}`;
      ok = words >= min && words <= max;
    }
    countEl.textContent = note;
    checkEl.disabled = busy || !ok || !state.signedIn || !state.enabled || state.remaining === 0;
  };

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
      const error = new Error(json?.data?.message || text.error || 'Error');
      error.data = json?.data || {};
      throw error;
    }
    return json.data;
  };

  const link = (href, label, primary) => {
    const a = document.createElement('a');
    a.href = href;
    a.textContent = label;
    a.className = primary ? 'efs-quiz__next efs-writing__link' : 'efs-writing__link efs-writing__link--plain';
    return a;
  };

  const showStatus = (message, proUrl) => {
    statusEl.replaceChildren();
    if (message) statusEl.append(document.createTextNode(message));
    if (proUrl && state.proDaily) {
      statusEl.append(document.createTextNode(` ${fmt(text.proMore, String(state.proDaily))} `));
      statusEl.append(link(proUrl, text.seePro || 'See Pro', false));
    }
  };

  const showQuota = () => {
    if (!state.signedIn || !state.enabled) return;
    const message = fmt(text.remaining, String(state.remaining), String(state.limit));
    showStatus(message, state.remaining === 0 ? state.proUrl : '');
  };

  const renderGuest = () => {
    const box = document.createElement('div');
    box.className = 'efs-writing__signin';
    const p = document.createElement('p');
    p.textContent = fmt(text.signIn, String(state.freeDaily || 3));
    const kept = document.createElement('p');
    kept.className = 'efs-writing__hint';
    kept.textContent = text.draftKept || '';
    const buttons = document.createElement('div');
    buttons.className = 'efs-writing__buttons';
    if (state.signupUrl) buttons.append(link(state.signupUrl, text.signUp || 'Create a free account', true));
    if (state.loginUrl) buttons.append(link(state.loginUrl, text.logIn || 'Log in', false));
    box.append(p, buttons, kept);
    checkEl.hidden = true;
    actionsEl.prepend(box);
  };

  const heading = (label) => {
    const h = document.createElement('h3');
    h.className = 'efs-writing__heading';
    h.textContent = label;
    return h;
  };

  const para = (value, className) => {
    const p = document.createElement('p');
    if (className) p.className = className;
    p.textContent = value;
    return p;
  };

  const renderResult = (data) => {
    resultEl.replaceChildren();

    if (data.cefr_estimate) {
      const est = document.createElement('p');
      est.className = 'efs-writing__estimate';
      est.append(document.createTextNode(`${text.estimate || 'Estimated level'}: `));
      const tag = document.createElement('strong');
      tag.textContent = data.cefr_estimate;
      est.append(tag);
      resultEl.append(est);
    }
    if (data.summary) resultEl.append(para(data.summary, 'efs-writing__summary'));

    if (data.strengths && data.strengths.length) {
      resultEl.append(heading(text.strengths || 'What went well'));
      const ul = document.createElement('ul');
      ul.className = 'efs-writing__strengths';
      data.strengths.forEach((s) => { const li = document.createElement('li'); li.textContent = s; ul.append(li); });
      resultEl.append(ul);
    }

    resultEl.append(heading(text.corrections || 'Corrections'));
    if (data.corrections && data.corrections.length) {
      const ol = document.createElement('ol');
      ol.className = 'efs-writing__corrections';
      data.corrections.forEach((c) => {
        const li = document.createElement('li');
        const was = document.createElement('del');
        was.textContent = c.original;
        const now = document.createElement('ins');
        now.textContent = c.corrected;
        const arrow = document.createElement('span');
        arrow.setAttribute('aria-hidden', 'true');
        arrow.textContent = ' → ';
        const line = document.createElement('p');
        line.className = 'efs-writing__change';
        line.append(was, arrow, now);
        li.append(line);
        if (c.explanation) li.append(para(c.explanation, 'efs-writing__why'));
        ol.append(li);
      });
      resultEl.append(ol);
    } else {
      resultEl.append(para(text.noCorrections || '', ''));
    }

    if (data.task_fit) {
      resultEl.append(heading(text.taskFit || 'The task'));
      resultEl.append(para(data.task_fit, ''));
    }

    if (data.next_step) {
      resultEl.append(heading(text.nextStep || 'Work on this next'));
      resultEl.append(para(data.next_step, 'efs-writing__next'));
    }

    if (data.improved_version) {
      const details = document.createElement('details');
      details.className = 'efs-writing__improved';
      const summary = document.createElement('summary');
      summary.textContent = text.improved || 'Show a corrected version';
      const body = document.createElement('div');
      data.improved_version.split(/\n+/).forEach((line) => body.append(para(line, '')));
      details.append(summary, body);
      resultEl.append(details);
    }

    if (data.noted > 0) resultEl.append(para(text.notebook || '', 'efs-writing__hint'));
    resultEl.append(para(text.disclaimer || '', 'efs-writing__hint'));

    const again = document.createElement('button');
    again.type = 'button';
    again.className = 'efs-quiz__next';
    again.textContent = text.again || 'Edit and check again';
    again.addEventListener('click', () => { textEl.focus(); textEl.scrollIntoView({ block: 'center' }); });
    resultEl.append(again);

    resultEl.hidden = false;
    resultEl.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
  };

  const check = async () => {
    if (busy || checkEl.disabled) return;
    busy = true;
    update();
    showStatus(text.checking || '…');
    resultEl.hidden = true;
    root.classList.add('is-busy');
    try {
      const data = await post('efs_writing_check', { nonce: state.nonce || '', task: taskEl.value, text: textEl.value });
      state.remaining = data.remaining;
      state.limit = data.limit;
      renderResult(data);
      showQuota();
      document.dispatchEvent(new CustomEvent('ef:progress', { detail: { source: 'efs-writing-feedback', kind: 'finished' } }));
    } catch (error) {
      if (error.data && error.data.code === 'limit') state.remaining = 0;
      showStatus(error.message, error.data && error.data.proUrl);
    } finally {
      busy = false;
      root.classList.remove('is-busy');
      update();
    }
  };

  levelEl.addEventListener('change', () => fillTasks(''));
  taskEl.addEventListener('change', () => { showTask(); saveDraft(); });
  textEl.addEventListener('input', () => { update(); saveDraft(); });
  checkEl.addEventListener('click', check);

  // Start from the task the learner last used, else the configured level.
  const lastTask = savedTask();
  const lastLevel = Object.keys(tasks).find((level) => (tasks[level] || []).some((t) => t.id === lastTask));
  levelEl.value = lastLevel || levelEl.value || config.level || 'B1';
  fillTasks(lastTask);

  post('efs_writing_status', {})
    .then((data) => {
      state = data || state;
      if (!state.signedIn) {
        renderGuest();
      } else if (!state.enabled) {
        showStatus(text.off || '');
      } else {
        // A learner with a Level Test result and no saved task starts at their level.
        if (!lastTask && state.level && tasks[state.level]) {
          levelEl.value = state.level;
          fillTasks('');
        }
        showQuota();
      }
      update();
    })
    .catch(() => {
      showStatus(text.error || '');
    });
})();
