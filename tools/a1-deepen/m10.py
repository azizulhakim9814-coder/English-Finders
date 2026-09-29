LESSONS = [
{
'id': 34998,
'hook': 'If something hurts, you need to say where. At the doctor\'s, at the pharmacy or when a friend asks "Are you OK?", body words are the first thing you need, before you can describe any problem.',
'notice': ['My <strong>head</strong> hurts.', 'She has long <strong>hair</strong> and brown <strong>eyes</strong>.', 'I broke my <strong>arm</strong> when I was ten.', 'He has a pain in his <strong>back</strong>.', 'My <strong>feet</strong> are tired after the walk.', 'Brush your <strong>teeth</strong> twice a day.'],
'notice_q': 'Which two body words have an unusual plural?',
'rule': '''<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr><th>Area</th><th>Words</th></tr>
<tr><td>Head and face</td><td>head · hair · face · eye(s) · ear(s) · nose · mouth · tooth (teeth) · neck</td></tr>
<tr><td>Upper body</td><td>shoulder · arm · elbow · hand · finger · chest · back · stomach</td></tr>
<tr><td>Lower body</td><td>leg · knee · foot (feet) · toe</td></tr>
</table>
<ul>
<li>Irregular plurals: <strong>foot → feet</strong>, <strong>tooth → teeth</strong>.</li>
<li>Use <strong>my / your / his / her</strong> with body parts, not <em>the</em>: <em>My head hurts.</em></li>
<li><strong>hurt</strong>: <em>My leg hurts. My eyes hurt.</em> (the body part is the subject)</li>
</ul>''',
'watch': [('The head hurts me.', 'My head hurts.', ''), ('two foots / two tooths', 'two feet / two teeth', ''),
          ('My eyes hurts.', 'My eyes hurt.', 'plural subject, no -s'), ('I have pain in the stomach.', 'I have a pain in my stomach. / I have a stomachache.', '')],
'practice': [('Plural of <em>foot</em>?', 'feet'), ('Plural of <em>tooth</em>?', 'teeth'), ('You smell with your ___.', 'nose'), ('You hear with your ___.', 'ears'),
             ('Complete: "___ (my / the) arm hurts."', 'My'), ('Name three parts of your face.', 'eyes, nose, mouth, ears… (any three)'), ('The middle of your leg is your ___.', 'knee'), ('Correct it: "My feet hurts."', 'My feet hurt.')],
'your_turn': 'Touch and name ten parts of your body in English, out loud. Then write three sentences: <em>My… is/are… · I have long / short… · My… hurts when…</em>',
'connect': [('Check body words and meanings in our Vocabulary Quiz (A1).', '/vocabulary-quiz/', 'Try our Vocabulary Quiz'),
            ('Our Hangman game is a fun way to practise spelling words like <em>shoulder</em> and <em>stomach</em>.', '/hangman-reimagined/', 'Play Hangman')],
'cefr': 'Spoken Production: I can name the basic parts of the body and say where something hurts.',
},
{
'id': 34999,
'hook': '"Are you OK? You look tired." How do you feel today: happy, tired, worried? Feelings words help you explain yourself, and notice how other people are, which makes you a much better conversation partner.',
'notice': ['I <strong>feel</strong> happy today.', 'She <strong>looks</strong> tired.', 'Why are you <strong>sad</strong>?', 'He\'s <strong>nervous</strong> about the test.', 'I\'m <strong>bored</strong>. There\'s nothing to do!', 'A: <strong>Are you OK?</strong> — B: Yes, I\'m fine. I\'m just <strong>hungry</strong>.'],
'notice_q': 'Which verbs come before the feelings words?',
'rule': '''<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr><th>Good feelings</th><th>Bad feelings</th><th>Body feelings</th></tr>
<tr><td>happy · excited · relaxed · proud · surprised</td><td>sad · angry · worried · scared / afraid · nervous · bored · lonely</td><td>tired · hungry · thirsty · hot · cold · sick / ill</td></tr>
</table>
<ul>
<li><strong>be / feel + feeling:</strong> <em>I\'m happy. I feel tired.</em></li>
<li><strong>look / seem + feeling</strong> (how someone appears): <em>You look worried. She seems excited.</em></li>
<li><strong>Why?</strong> — <em>I\'m nervous <strong>because</strong> I have an exam.</em></li>
<li><strong>Asking:</strong> How are you? · How do you feel? · Are you OK? · What\'s wrong?</li>
<li><strong>bored</strong> (how you feel) ≠ <strong>boring</strong> (the thing): <em>The film is boring, so I\'m bored.</em></li>
</ul>''',
'watch': [('I am boring.', 'I am bored.', 'boring = the thing'), ('She looks like tired.', 'She looks tired.', 'look + adjective'),
          ('I have hungry.', 'I am hungry.', ''), ('I feel myself sad.', 'I feel sad.', '')],
'practice': [('Complete: I ___ (feel) very tired today.', 'feel'), ('Complete: She ___ (look) worried.', 'looks'), ('You got a great present. You feel ___.', 'happy / excited'), ('You have a big test tomorrow. You feel ___.', 'nervous / worried'),
             ('Ask how someone feels.', 'How do you feel? / Are you OK?'), ('bored or boring? "This lesson isn\'t ___!"', 'boring'), ('You want a drink. You are ___.', 'thirsty'), ('Join with <em>because</em>: "I\'m happy. It\'s my birthday."', 'I\'m happy because it\'s my birthday.')],
'your_turn': 'Write five sentences about how you feel in different situations: <em>I feel nervous when… I\'m happy when… I get bored when…</em> Then write how a friend or family member looks today.',
'connect': [('Check feelings words in our Vocabulary Quiz (A1).', '/vocabulary-quiz/', 'Try our Vocabulary Quiz'),
            ('Match feelings words to their meanings in our Match the Definition game.', '/match-the-definition/', 'Try our Match the Definition game')],
'cefr': 'Spoken Production: I can describe how I feel using simple words and give a simple reason.',
},
{
'id': 35000,
'hook': 'You wake up and you don\'t feel well. How do you explain what\'s wrong, to a friend, a teacher or a pharmacist? And when a friend is ill, how do you give them advice? Two patterns do most of the work: <strong>I have a…</strong> and <strong>You should…</strong>',
'notice_intro': 'Read the conversation:',
'notice': ['A: <strong>What\'s wrong?</strong>', 'B: I don\'t feel well. <strong>I have a headache</strong> and <strong>a sore throat</strong>.', 'A: Oh no. <strong>You should</strong> drink some water and rest.', 'B: I also <strong>have a temperature</strong>.', 'A: Then <strong>you should</strong> see a doctor. And <strong>you shouldn\'t</strong> go to work today.'],
'notice_q': 'What form of the verb comes after <em>should</em>?',
'rule': '''<p><strong>Symptoms: I have a / I have…</strong></p>
<ul>
<li>a headache · a stomachache · a toothache · a backache · a sore throat</li>
<li>a cold · a cough · a temperature / a fever · the flu</li>
<li>My arm / leg hurts. · I feel sick. · I\'m tired.</li>
</ul>
<p><strong>Advice: should / shouldn\'t + base verb</strong> (the same for all persons)</p>
<ul>
<li><em>You <strong>should</strong> rest. You <strong>should</strong> take some medicine. You <strong>should</strong> see a doctor.</em></li>
<li><em>You <strong>shouldn\'t</strong> go to school. You <strong>shouldn\'t</strong> eat too much.</em></li>
<li>Question: <em><strong>Should</strong> I stay at home?</em> — Yes, you should. / No, you shouldn\'t.</li>
</ul>
<p>Sympathy: <em>Oh no! · I\'m sorry to hear that. · Get well soon!</em></p>''',
'watch': [('You should to rest.', 'You should rest.', 'no "to"'), ('He shoulds see a doctor.', 'He should see a doctor.', ''),
          ('I have headache.', 'I have a headache.', ''), ('My head is aching me.', 'My head hurts. / I have a headache.', '')],
'practice': [('Complete: I have a ___ (headache / head).', 'headache'), ('Give advice: rest', 'You should rest.'), ('Give negative advice: go to work', 'You shouldn\'t go to work.'), ('Complete: You ___ see a doctor.', 'should'),
             ('Good advice for a sore throat?', 'drink warm tea / rest your voice (any sensible answer)'), ('Correct it: "She should to take medicine."', 'She should take medicine.'), ('Say something kind to an ill friend.', 'Get well soon! / I\'m sorry to hear that.'), ('Ask for advice: I / stay at home?', 'Should I stay at home?')],
'your_turn': 'Write three mini-dialogues: a friend has (1) a cold, (2) a stomachache, (3) a toothache. Each time, ask <em>What\'s wrong?</em>, show sympathy and give two pieces of advice.',
'connect': [('Fix mistakes like "You should to rest" in our Error Correction tool.', '/error-correction/', 'Try Error Correction'),
            ('Our Grammar Quiz has A1 questions on should and shouldn\'t.', '/grammar-quiz/', 'Try our Grammar Quiz')],
'cefr': 'Spoken Interaction: I can describe simple symptoms and give or understand simple advice.',
},
{
'id': 35001,
'hook': '"Lovely day, isn\'t it?" "Terrible weather today!" In many English-speaking countries, the weather is the most common small-talk topic of all. It is a safe, friendly way to start a conversation with anyone.',
'notice': ['<strong>It\'s sunny</strong> today.', '<strong>It\'s raining</strong> and it\'s <strong>cold</strong>.', '<strong>What\'s the weather like</strong> today? — It\'s cloudy and windy.', 'It\'s very <strong>hot</strong> in <strong>summer</strong> and it <strong>snows</strong> in <strong>winter</strong>.', 'I love <strong>spring</strong> because it\'s <strong>warm</strong> and sunny.'],
'notice_q': 'What word starts almost every weather sentence?',
'rule': '''<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr><th>Noun</th><th>Adjective (It\'s…)</th><th>Verb (It\'s …ing now)</th></tr>
<tr><td>sun</td><td>sunny</td><td>—</td></tr>
<tr><td>rain</td><td>rainy</td><td>It\'s raining.</td></tr>
<tr><td>snow</td><td>snowy</td><td>It\'s snowing.</td></tr>
<tr><td>cloud</td><td>cloudy</td><td>—</td></tr>
<tr><td>wind</td><td>windy</td><td>—</td></tr>
<tr><td>fog</td><td>foggy</td><td>—</td></tr>
</table>
<p>Temperature: <strong>hot → warm → cool → cold → freezing</strong>. Seasons: <strong>spring, summer, autumn (US: fall), winter</strong> — use <em>in</em>: <em>in summer</em>.</p>
<p>We use <strong>It</strong> for weather: <em>It\'s cold</em> (not <em>Is cold</em>, not <em>The weather is raining</em>). Ask: <strong>What\'s the weather like?</strong> · <strong>What\'s the temperature?</strong> — It\'s 25 degrees.</p>''',
'watch': [('Is cold today.', 'It\'s cold today.', ''), ('It\'s rain.', 'It\'s raining. / It\'s rainy.', ''),
          ('How is the weather like?', 'What\'s the weather like? / How\'s the weather?', ''), ('on summer', 'in summer', 'seasons use "in"')],
'practice': [('Complete: ___ sunny today.', 'It\'s'), ('Name the four seasons.', 'spring, summer, autumn (fall), winter'), ('Ask about the weather.', 'What\'s the weather like today?'), ('Which season is very cold, with snow?', 'winter'),
             ('Adjective from <em>wind</em>?', 'windy'), ('Complete: "Take an umbrella. It\'s ___ (rain)."', 'raining'), ('Between <em>hot</em> and <em>cool</em> is ___.', 'warm'), ('Write a sentence describing today\'s weather.', 'It\'s cloudy and cool today. (your own answer)')],
'your_turn': 'Describe today\'s weather in two sentences. Then write about the weather in your country in each season and say which season you like best and why.',
'connect': [('Our Daily Unscramble game is a quick daily word puzzle: a nice way to keep your vocabulary fresh.', '/daily-unscramble/', 'Play Daily Unscramble'),
            ('Check weather words in our Vocabulary Quiz (A1).', '/vocabulary-quiz/', 'Try our Vocabulary Quiz')],
'cefr': 'Spoken Production / Reading: I can describe the weather and understand simple weather information.',
},
{
'id': 35002,
'hook': 'Now put it all together: body parts, feelings, symptoms and advice, in one realistic conversation at the doctor\'s. This is a situation where clear, simple English really matters.',
'notice_intro': 'Read the dialogue:',
'notice': ['<strong>Receptionist:</strong> Good morning. Do you have an appointment?', '<strong>Patient:</strong> Yes, at ten o\'clock. My name is Leo Rossi.', '<strong>Doctor:</strong> Hello, Leo. Come in. <strong>What\'s wrong?</strong>', '<strong>Patient:</strong> <strong>I don\'t feel well.</strong> I have a headache and a sore throat.', '<strong>Doctor:</strong> <strong>When did it start?</strong>', '<strong>Patient:</strong> Two days ago.', '<strong>Doctor:</strong> Do you have a temperature? — <strong>Patient:</strong> Yes, a little.', '<strong>Doctor:</strong> It\'s a cold. You should rest and drink lots of water. Take this medicine three times a day. You shouldn\'t go to work today.', '<strong>Patient:</strong> Thank you, doctor.'],
'notice_q': 'Which questions does the doctor ask?',
'rule': '''<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr><th>The doctor says…</th><th>The patient says…</th></tr>
<tr><td>What\'s wrong? / What\'s the problem?</td><td>I don\'t feel well. / I have a… / My… hurts.</td></tr>
<tr><td>When did it start?</td><td>Yesterday. / Two days ago.</td></tr>
<tr><td>Do you have a temperature?</td><td>Yes, a little. / No, I don\'t.</td></tr>
<tr><td>You should… / You shouldn\'t…</td><td>Should I…? / How often should I take it?</td></tr>
<tr><td>Take this twice a day.</td><td>Thank you, doctor.</td></tr>
</table>
<p>At the reception: <em>I\'d like to make an appointment, please. · Can I see a doctor today?</em></p>''',
'watch': [('I am not feel well.', 'I don\'t feel well.', ''), ('It started before two days.', 'It started two days ago.', ''),
          ('How many times I take it?', 'How often should I take it?', '')],
'practice': [('What does the doctor ask first?', 'What\'s wrong? / What\'s the problem?'), ('Which phrase means "I am sick"?', 'I don\'t feel well.'), ('Complete: "Do you ___ a temperature?"', 'have'), ('Give one piece of advice with <em>should</em>.', 'You should rest. (any sensible advice)'),
             ('Answer "When did it start?" (three days)', 'Three days ago.'), ('Ask how often to take medicine.', 'How often should I take it?'), ('Ask for an appointment at reception.', 'I\'d like to make an appointment, please.'), ('What does "twice a day" mean?', 'two times every day')],
'your_turn': 'Write your own doctor\'s visit dialogue of 8–10 lines with a different problem (a cough, a stomachache, a back pain). Include a time expression and two pieces of advice. Act it out with a friend or alone.',
'connect': [('Practise saying health phrases clearly with our Pronunciation Practice.', '/pronunciation-practice/', 'Try Pronunciation Practice'),
            ('Build doctor\'s-visit questions in the right order with our Sentence Builder.', '/sentence-builder/', 'Try our Sentence Builder')],
'cefr': 'Spoken Interaction: I can take part in a simple conversation about health, if the other person helps.',
},
{
'id': 35003, 'kind': 'cando',
'task': 'Choose one task (or do both). <strong>Option A:</strong> write and act out a doctor\'s visit (8–10 lines) with at least two symptoms, one time expression and two pieces of advice. <strong>Option B:</strong> write a short weather report for your town today (5–6 sentences) and say how the weather makes you feel.',
'steps': ['Option A: decide the problem and when it started. Write the patient\'s lines first, then the doctor\'s questions and advice.', 'Option B: look outside or check a weather app. Note the sky, the temperature and the wind.', 'Use <em>I have a…</em> and <em>should / shouldn\'t</em> (A) or <em>It\'s…</em> and <em>It\'s …ing</em> (B).', 'Add a feelings sentence with <em>because</em>.', 'Say it out loud. For Option A, try it with a partner.'],
'language': '''<ul>
<li><strong>Doctor:</strong> What\'s wrong? · When did it start? · Do you have a temperature? · You should… · You shouldn\'t…</li>
<li><strong>Patient:</strong> I don\'t feel well. · I have a headache / a cough / a sore throat. · My back hurts. · Two days ago.</li>
<li><strong>Weather:</strong> It\'s sunny / cloudy / windy / cold. · It\'s raining. · It\'s 18 degrees.</li>
<li><strong>Feelings:</strong> I feel happy / tired / bored because…</li>
</ul>''',
'model': '<strong>Option A:</strong> Doctor: Good morning. What\'s wrong? / Patient: I don\'t feel well. I have a cough and my chest hurts. / Doctor: When did it start? / Patient: Three days ago. / Doctor: Do you have a temperature? / Patient: No, I don\'t. / Doctor: You should drink hot tea with honey and rest. You shouldn\'t go running this week. / Patient: Thank you, doctor.<br /><strong>Option B:</strong> Good morning! Here is the weather for Alexandria today. It\'s sunny and warm, about 26 degrees. It\'s a little windy near the sea. It isn\'t raining. I feel happy because I love sunny days!',
'checklist': ['A: I used at least two symptoms with <em>I have a…</em> or <em>…hurts</em>.', 'A: I used <em>should</em> and <em>shouldn\'t</em> + base verb.', 'B: I used <em>It\'s</em> + weather adjective and a temperature.', 'I added a feeling with <em>because</em>.', 'I said my text out loud.'],
'extend': 'Do the other option too. Then write three pieces of advice for staying healthy in winter: <em>You should… You shouldn\'t…</em>',
'connect': [('Review this module\'s grammar with our Grammar Quiz (choose A1).', '/grammar-quiz/', 'Try our Grammar Quiz')],
'cefr': 'Spoken Interaction / Writing: I can take part in a simple conversation about health and describe everyday conditions like the weather.',
},
{
'id': 35004, 'kind': 'worksheet',
'parts': [
 ('Part A – Body parts', 'Write the body part for each clue.', ['You use these to see.', 'You use this to smell.', 'You have ten of these on your hands.', 'The plural of <em>foot</em>.', 'The plural of <em>tooth</em>.'], ''),
 ('Part B – Symptom and advice matching', 'Match each problem (a–d) to the best advice (1–4). Problems: a) headache · b) sore throat · c) fever · d) stomachache. Advice: 1) drink warm tea · 2) rest and drink lots of water · 3) eat something light · 4) take some medicine and lie down.', [], ''),
 ('Part C – Feelings', '', ['Complete: I ___ (feel) very happy today.', 'Complete: She ___ (look) tired.', 'You have a big test tomorrow. You feel ___.', 'bored or boring? The film was ___.'], ''),
 ('Part D – Weather', '', ['Complete: ___ raining today.', 'Name the season with the coldest weather.', 'Ask about the weather.', 'Adjective from <em>cloud</em>?'], ''),
 ('Part E – Extra practice (online): should / shouldn\'t', 'Give advice.', ['My friend has a cold. He ___ (rest).', 'You have a toothache. You ___ (eat) sweets.', 'She\'s very tired. She ___ (go) to bed early.'], ''),
],
'key': [('Part A', '1 eyes · 2 nose · 3 fingers · 4 feet · 5 teeth'),
        ('Part B', 'a → 4 · b → 1 · c → 2 · d → 3 (2 and 4 both work for a and c)'),
        ('Part C', '1 feel · 2 looks · 3 nervous / worried · 4 boring'),
        ('Part D', '1 It\'s · 2 winter · 3 What\'s the weather like today? · 4 cloudy'),
        ('Part E', '1 should rest · 2 shouldn\'t eat · 3 should go')],
'connect': [('Review body, feelings and weather words with our Vocabulary Quiz (A1).', '/vocabulary-quiz/', 'Try our Vocabulary Quiz')],
'cefr': 'Vocabulary / Reading: I can understand and use basic health, feelings and weather words.',
},
]
