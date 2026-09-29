(() => {
  'use strict';

  const config = window.efsPronunciationConfig || {};
  const text = config.i18n || {};
  const roots = Array.from(document.querySelectorAll('[data-efs-pronunciation]'));
  if (!roots.length) return;

  const SpeechRecognitionCtor = window.SpeechRecognition || window.webkitSpeechRecognition;

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

  // Loose on purpose: this compares a browser-generated transcript against
  // a dictionary word, not two exact strings a server produced. Stripping
  // punctuation and accepting the target as any whole word in the
  // transcript tolerates a stray filler word ("the elephant" for
  // "elephant") without accepting a different, merely similar-sounding one.
  const clean = (value) => (value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
  const isMatch = (transcript, target) => {
    const heard = clean(transcript);
    const word = clean(target);
    if (!word) return false;
    if (!word.includes(' ')) return heard.split(' ').includes(word);
    return ` ${heard} `.includes(` ${word} `);
  };

  roots.forEach((root) => {
    const body = root.querySelector('[data-efs-pronunciation-body]');
    const scoreEl = root.querySelector('[data-efs-pronunciation-score]');
    const levelEl = root.querySelector('[data-efs-pronunciation-level]');

    const message = (content) => {
      body.replaceChildren();
      const p = document.createElement('p');
      p.className = 'efs-quiz__message';
      p.textContent = content;
      body.append(p);
    };

    if (!SpeechRecognitionCtor) {
      message(text.unsupported || "Your browser doesn't support speech recognition.");
      return;
    }

    const sessionLength = Number(config.session) > 0 ? Number(config.session) : 15;

    let asked = 0;
    let right = 0;
    let word = '';
    let locked = false;
    let listening = false;

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
      score.textContent = (text.doneBody || 'You pronounced %1$s of %2$s words correctly.')
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

    const nextRound = async () => {
      locked = false;
      listening = false;
      message(text.loading || 'Loading…');

      let round;
      try {
        round = await post('efs_pronunciation_round', { level: levelEl ? levelEl.value : (config.level || '') });
      } catch (error) {
        message(error.message);
        return;
      }

      word = round.word;
      body.replaceChildren();

      const wordEl = document.createElement('p');
      wordEl.className = 'efs-quiz__word';
      wordEl.textContent = word;
      body.append(wordEl);

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

      const actions = document.createElement('div');
      actions.className = 'efs-pronunciation__actions';

      if (round.audioUrl) {
        const audio = new Audio(round.audioUrl);
        const listenButton = document.createElement('button');
        listenButton.type = 'button';
        listenButton.className = 'efs-spelling__play';
        listenButton.textContent = text.listen || 'Listen';
        listenButton.addEventListener('click', () => audio.play());
        actions.append(listenButton);
      }

      const speakButton = document.createElement('button');
      speakButton.type = 'button';
      speakButton.className = 'efs-pronunciation__speak';
      speakButton.textContent = text.speak || 'Speak';
      actions.append(speakButton);
      body.append(actions);

      const status = document.createElement('p');
      status.className = 'efs-quiz__message';
      status.setAttribute('role', 'status');
      body.append(status);

      const recognition = new SpeechRecognitionCtor();
      recognition.lang = 'en-US';
      recognition.continuous = false;
      recognition.interimResults = false;
      recognition.maxAlternatives = 1;

      recognition.addEventListener('result', (event) => {
        const heard = event.results[0]?.[0]?.transcript || '';
        score(heard);
      });

      recognition.addEventListener('error', (event) => {
        listening = false;
        speakButton.disabled = false;
        speakButton.classList.remove('is-listening');
        if (locked) return;

        if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
          status.textContent = text.micDenied || 'Microphone access was blocked.';
        } else if (event.error === 'no-speech') {
          status.textContent = text.noSpeech || "Didn't catch that — try again.";
        } else {
          status.textContent = text.micError || 'Something went wrong with the microphone. Try again.';
        }
      });

      recognition.addEventListener('end', () => {
        listening = false;
        if (!locked) {
          speakButton.disabled = false;
          speakButton.classList.remove('is-listening');
        }
      });

      speakButton.addEventListener('click', () => {
        if (locked || listening) return;
        listening = true;
        speakButton.disabled = true;
        speakButton.classList.add('is-listening');
        status.textContent = text.listening || 'Listening…';
        try {
          recognition.start();
        } catch (error) {
          // Some browsers throw if start() is called while already running
          // from a stray previous session; resetting lets the next click work.
          listening = false;
          speakButton.disabled = false;
          speakButton.classList.remove('is-listening');
          status.textContent = text.micError || 'Something went wrong with the microphone. Try again.';
        }
      });

      const score = (heard) => {
        if (locked) return;
        locked = true;
        speakButton.disabled = true;

        const correct = isMatch(heard, word);
        asked += 1;
        if (correct) right += 1;
        // Shared English Finders signal (EFS 1.13.1): English Finders Account invites guests to save their progress.
        if (correct) document.dispatchEvent(new CustomEvent('ef:progress', { detail: { source: 'efs-pronunciation', kind: 'correct' } }));
        showScore();

        status.textContent = `${text.heardLabel || 'You said:'} "${heard}"`;

        const verdict = document.createElement('p');
        verdict.className = correct ? 'efs-quiz__verdict is-correct' : 'efs-quiz__verdict is-wrong';
        verdict.textContent = correct ? (text.correct || 'Correct') : (text.wrong || 'Not quite');
        body.append(verdict);

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
