LESSONS = [
{
'id': 34953,
'hook': 'You arrive in a new town. You need cash, medicine, a train ticket and something to eat. Before you can ask for directions, you need the names of the places you are looking for, and the things people do there.',
'notice': ['I need some money. Is there a <strong>bank</strong> or a <strong>cash machine</strong> near here?', 'I have a headache. I\'m going to the <strong>pharmacy</strong>.', 'The train leaves from the <strong>station</strong> at 9.', 'We buy our food at the <strong>supermarket</strong>.', 'Let\'s meet at the <strong>café</strong> opposite the <strong>park</strong>.', 'The <strong>bus stop</strong> is next to the <strong>post office</strong>.'],
'notice_q': 'Which places do you visit every week?',
'rule': '''<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr><th>Place</th><th>What you do there</th></tr>
<tr><td>bank / cash machine (ATM)</td><td>get or change money</td></tr>
<tr><td>pharmacy (UK: chemist\'s)</td><td>buy medicine</td></tr>
<tr><td>post office</td><td>send letters and parcels</td></tr>
<tr><td>station / bus stop</td><td>catch a train / a bus</td></tr>
<tr><td>supermarket</td><td>buy food and things for the house</td></tr>
<tr><td>hospital</td><td>see a doctor when you are very ill</td></tr>
<tr><td>library</td><td>borrow books, study</td></tr>
<tr><td>museum</td><td>look at art and history</td></tr>
<tr><td>restaurant / café</td><td>eat a meal / have a drink or a snack</td></tr>
<tr><td>hotel</td><td>stay for a night</td></tr>
<tr><td>park</td><td>walk, relax, play</td></tr>
<tr><td>cinema</td><td>watch a film</td></tr>
</table>
<p>Use <strong>at</strong> or <strong>in</strong> with places: <em>I\'m at the station. She works in a bank.</em> Use <strong>to</strong> for movement: <em>I\'m going to the pharmacy.</em></p>''',
'watch': [('I go to pharmacy.', 'I go to the pharmacy.', 'use "the"'), ('a library = a shop that sells books', 'a library = where you borrow books', 'a bookshop sells books'),
          ('I\'m going at the bank.', 'I\'m going to the bank.', 'movement uses "to"')],
'practice': [('Where do you buy medicine?', 'at the pharmacy'), ('Where do you catch a train?', 'at the station'), ('Where do you borrow books?', 'at the library'), ('Where do you send a parcel?', 'at the post office'),
             ('Where do you watch a film?', 'at the cinema'), ('Where do you stay on holiday?', 'at a hotel'), ('Complete: "I\'m going ___ the bank."', 'to'), ('A library or a bookshop: where do you <em>buy</em> books?', 'a bookshop')],
'your_turn': 'Write six places near your home and one sentence for each: <em>There\'s a pharmacy near my house. I go there when I\'m ill.</em>',
'connect': [('Check meanings of town words in our Vocabulary Quiz (choose A1).', '/vocabulary-quiz/', 'Try our Vocabulary Quiz'),
            ('Our Hangman game is a fun way to practise spelling everyday words.', '/hangman-reimagined/', 'Play Hangman')],
'cefr': 'Reading: I can understand familiar names and words on signs, for example in a town or at a station.',
},
{
'id': 34954,
'hook': 'A tourist stops you: "Excuse me, where\'s the station?" You know the way. Now you need the words to tell them: short instructions like <strong>turn left</strong>, <strong>go straight on</strong> and <strong>it\'s on the corner</strong>.',
'notice': ['<strong>Go straight on</strong> for about two minutes.', '<strong>Turn left</strong> at the traffic lights.', '<strong>Take the second right</strong>.', '<strong>Go past</strong> the bank.', '<strong>Cross</strong> the road.', 'It\'s <strong>on the left</strong>, <strong>next to</strong> the café. / It\'s <strong>on the corner</strong>.'],
'notice_q': 'Is there a subject (<em>you</em>) in these instructions?',
'rule': '''<p>Directions are <strong>imperatives</strong> (verb, no subject, as in Module 0): <em>Go. Turn. Cross. Take.</em></p>
<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr><th>Instruction</th><th>Meaning</th></tr>
<tr><td>Go straight on / along this street</td><td>don\'t turn; continue</td></tr>
<tr><td>Turn left / right (at the…)</td><td>change direction</td></tr>
<tr><td>Take the first / second left</td><td>turn into the first / second street on the left</td></tr>
<tr><td>Go past the…</td><td>walk by it, don\'t stop</td></tr>
<tr><td>Go through the park</td><td>from one side to the other, inside it</td></tr>
<tr><td>Cross the road / the bridge</td><td>go to the other side</td></tr>
<tr><td>It\'s on the left / right / on the corner / at the end of the street</td><td>where it is when you arrive</td></tr>
</table>
<p>Put the steps in order with <strong>first, then, after that</strong>, and finish with where the place is: <em>First go straight on, then turn right. It\'s on the left, opposite the park.</em></p>''',
'watch': [('You turn left.', 'Turn left.', 'no subject in directions'), ('Go straight.', 'Go straight on.', ''),
          ('Turn to left.', 'Turn left.', ''), ('It\'s in the left.', 'It\'s on the left.', '')],
'practice': [('___ left at the corner.', 'Turn'), ('___ straight on for two blocks.', 'Go'), ('Go ___ the park to get to the station. (from one side to the other)', 'through'), ('___ the road at the traffic lights.', 'Cross'),
             ('Go ___ the bank, don\'t stop. (walk by it)', 'past'), ('Take the ___ right. (street number 2)', 'second'), ('It\'s ___ the corner.', 'on'), ('Correct it: "You go straight and you turn to right."', 'Go straight on and turn right.')],
'your_turn': 'Write directions from your home to the nearest shop or bus stop, in 4–5 steps. Use <em>first, then, after that</em> and finish with where the place is.',
'connect': [('Put direction sentences in the right order with our Sentence Builder.', '/sentence-builder/', 'Try our Sentence Builder'),
            ('Our Word Chain Challenge is a quick game for building vocabulary.', '/word-chain-challenge/', 'Play Word Chain Challenge')],
'cefr': 'Spoken Interaction: I can give simple directions and instructions.',
},
{
'id': 34955,
'hook': 'Giving directions is half the skill. The other half is asking politely, understanding the answer, and asking again when you don\'t understand. That last part is a real A1 skill, not a weakness: everyone does it.',
'notice': ['<strong>Excuse me, where\'s</strong> the bank?', '<strong>How do I get to</strong> the station?', '<strong>Is there a</strong> pharmacy <strong>near here</strong>?', '<strong>Is it far?</strong> — No, it\'s about five minutes\' walk.', '<strong>Sorry, could you repeat that, please?</strong>', '<strong>Could you speak more slowly, please?</strong>', '<strong>Thank you very much.</strong> — You\'re welcome.'],
'notice_q': 'Which two words start every polite question to a stranger?',
'rule': '''<p><strong>Asking:</strong> start with <strong>Excuse me</strong>, then:</p>
<ul>
<li><strong>Where is (Where\'s) the…?</strong> — for a place you know is there</li>
<li><strong>How do I get to the…?</strong> — for the way</li>
<li><strong>Is there a… near here?</strong> — when you don\'t know if there is one</li>
<li><strong>Is it far?</strong> / <strong>How far is it?</strong> — It\'s about 10 minutes on foot / by bus.</li>
</ul>
<p><strong>When you don\'t understand:</strong> Sorry, could you repeat that? · Could you speak more slowly? · Left or right? · Can you show me on the map?</p>
<p><strong>Checking:</strong> repeat the directions back: <em>So, straight on, then the second left?</em></p>
<h3>Dialogue</h3>
<blockquote>A: Excuse me, is there a post office near here?<br />B: Yes, there\'s one on Park Road. Go straight on and take the first left. It\'s next to the bank.<br />A: Sorry, could you repeat that, please?<br />B: Sure. Straight on, first left. It\'s next to the bank.<br />A: Is it far?<br />B: No, it\'s about five minutes\' walk.<br />A: Thank you very much!<br />B: You\'re welcome.</blockquote>''',
'watch': [('Where is bank?', 'Where is the bank?', ''), ('How I get to the station?', 'How do I get to the station?', ''),
          ('Repeat!', 'Sorry, could you repeat that, please?', 'more polite'), ('Is there pharmacy near?', 'Is there a pharmacy near here?', '')],
'practice': [('Complete: "___, where is the station?"', 'Excuse me'), ('"How do I ___ to the hospital?"', 'get'), ('You don\'t know if there is a bank. Ask.', 'Is there a bank near here?'), ('You didn\'t understand. Ask politely.', 'Sorry, could you repeat that, please?'),
             ('Ask about distance.', 'Is it far? / How far is it?'), ('Reply to "Thank you very much!"', 'You\'re welcome.'), ('Put in order: I / get / how / the museum / do / to?', 'How do I get to the museum?'), ('Check the directions: "straight on, second right".', 'So, straight on, then the second right?')],
'your_turn': 'Read the dialogue out loud twice, once as A and once as B. Then write your own dialogue of 6–8 lines for a different place (the cinema, a hotel, the library).',
'connect': [('Build polite questions like "How do I get to the station?" in our Sentence Builder.', '/sentence-builder/', 'Try our Sentence Builder'),
            ('Our Grammar Quiz has A1 questions on questions and imperatives.', '/grammar-quiz/', 'Try our Grammar Quiz')],
'cefr': 'Spoken Interaction: I can interact in a simple way provided the other person is prepared to repeat or rephrase things more slowly.',
},
{
'id': 34956,
'hook': '"<strong>Can I</strong> have a map, please?" "<strong>Can you</strong> help me, please?" The word <em>can</em> is not only for ability. It is also the easiest way to ask for things and ask people to do things, politely.',
'notice': ['<strong>Can I</strong> have a coffee, please? — Sure. / Of course.', '<strong>Can I</strong> pay by card? — Yes, you can. / Sorry, only cash.', '<strong>Can you</strong> help me, please? — Yes, of course.', '<strong>Can you</strong> show me the way? — Sorry, I\'m not from here.', '<strong>Could you</strong> say that again, please? (a little more polite)'],
'notice_q': 'When do we use <em>Can I…?</em> and when do we use <em>Can you…?</em>',
'rule': '''<table border="1" cellpadding="6" style="border-collapse:collapse;width:100%">
<tr><th>Pattern</th><th>Use it to…</th><th>Example</th></tr>
<tr><td>Can I + verb…?</td><td>ask for something / ask for permission</td><td>Can I sit here? · Can I have the bill?</td></tr>
<tr><td>Can you + verb…?</td><td>ask someone to do something</td><td>Can you open the door, please?</td></tr>
<tr><td>Could I / Could you…?</td><td>the same, more polite</td><td>Could you help me, please?</td></tr>
</table>
<ul>
<li>After <em>can/could</em> use the <strong>base verb</strong>: <em>Can I have</em> (not <em>Can I to have</em>, not <em>Can I has</em>).</li>
<li>Add <strong>please</strong>: at the end, or after <em>you</em>: <em>Can you please…?</em></li>
<li><strong>Yes:</strong> Sure. · Of course. · No problem. · Here you are. <strong>No:</strong> Sorry, I can\'t. · I\'m afraid not.</li>
</ul>''',
'watch': [('Can I to have a water?', 'Can I have some water, please?', ''), ('Give me the menu.', 'Can I have the menu, please?', 'more polite'),
          ('Can you to help me?', 'Can you help me?', ''), ('Can I have a coffee? — Yes, you have.', 'Sure. / Here you are.', '')],
'practice': [('___ I have a glass of water, please?', 'Can / Could'), ('___ you help me find the station, please?', 'Can / Could'), ('Make it polite: "Give me the bill."', 'Can I have the bill, please?'), ('Make it polite: "Close the window."', 'Can you close the window, please?'),
             ('Correct it: "Can I to pay by card?"', 'Can I pay by card?'), ('Say yes to "Can I sit here?"', 'Sure. / Of course.'), ('Say no politely.', 'Sorry, I\'m afraid not.'), ('Which is more polite: <em>can</em> or <em>could</em>?', 'could')],
'your_turn': 'Write five requests you might make while travelling: at a hotel, a café, a station and in the street. Use <em>Can I…?</em> twice and <em>Can you…? / Could you…?</em> three times.',
'connect': [('Fix mistakes like "Can I to have…" in our Error Correction tool.', '/error-correction/', 'Try Error Correction'),
            ('Build polite requests word by word in our Sentence Builder.', '/sentence-builder/', 'Try our Sentence Builder')],
'cefr': 'Spoken Interaction: I can ask people for things and give people things; I can make simple requests.',
},
{
'id': 34957, 'kind': 'cando',
'task': 'Ask for and give directions. Draw a simple town map (or use a real one) with at least six places and a starting point. Write a dialogue of 8–10 lines: one person asks the way politely, the other gives directions, and the first person checks or asks them to repeat. Then act it out, alone or with a partner.',
'steps': ['Draw the map: streets, six places, and an X for "you are here".', 'Choose the place you want to go to, and trace the route with your finger.', 'Write the question: <em>Excuse me, how do I get to…?</em>', 'Write the directions in 3–4 steps with imperatives and <em>first / then</em>.', 'Add a "repeat" line, a "check" line and a thank-you.'],
'language': '''<ul>
<li><strong>Asking:</strong> Excuse me, where\'s the…? · How do I get to the…? · Is there a… near here? · Is it far?</li>
<li><strong>Directions:</strong> Go straight on · Turn left/right · Take the first/second left · Go past · Cross the road</li>
<li><strong>Where it is:</strong> It\'s on the left/right · on the corner · next to · opposite · between</li>
<li><strong>Help:</strong> Sorry, could you repeat that? · So, first left, then…?</li>
</ul>''',
'model': 'A: Excuse me, how do I get to the pharmacy?<br />B: Go straight on, then turn right at the traffic lights. Go past the bank. The pharmacy is on the corner, next to the supermarket.<br />A: Sorry, could you repeat that, please?<br />B: Sure. Straight on, right at the lights, past the bank. It\'s on the corner.<br />A: So, right at the lights and past the bank. Is it far?<br />B: No, it\'s about five minutes\' walk.<br />A: Thank you very much!<br />B: You\'re welcome.',
'checklist': ['I started with <em>Excuse me</em>.', 'I used at least four direction imperatives.', 'I said where the place is at the end (<em>on the corner, next to…</em>).', 'I asked someone to repeat, or checked the directions.', 'My directions really work on my map!', 'I acted out the dialogue out loud.'],
'extend': 'Write directions from your home to your favourite place in town. Then give them to a friend and see if they can follow them on a map.',
'connect': [('Review this module\'s grammar with our Grammar Quiz (choose A1).', '/grammar-quiz/', 'Try our Grammar Quiz')],
'cefr': 'Spoken Interaction: I can ask for and give simple directions, and ask people to repeat. Writing: I can write simple instructions.',
},
{
'id': 34958, 'kind': 'worksheet',
'image': '<p><img class="alignnone size-medium wp-image-35144" src="https://englishfinders.com/wp-content/uploads/2026/09/Worksheet-5-Town-Map-Direction-Writing-232x300.jpg" alt="Worksheet 5: Town Map + Direction Writing" width="232" height="300" /></p>',
'parts': [
 ('Part A – Map task', 'Draw a simple town map with 6 places (bank, pharmacy, station, park, supermarket, school). Mark a starting point with an X.', [], ''),
 ('Part B – Write the directions', 'Write directions from your starting point (X) to 2 different places on your map, using at least 4 imperatives and 2 prepositions of movement.', [], ''),
 ('Part C – Gap fill', 'Complete:', ['___ straight on.', '___ left at the corner.', 'Go ___ the park.', '___ me, where is the bank?'], ''),
 ('Part D – Extra practice (online): places', 'Where do you go?', ['You want to send a parcel.', 'You need some medicine.', 'You want to borrow a book.', 'You want to catch a train.'], ''),
 ('Part E – Extra practice (online): polite requests', 'Make each request polite with <em>Can I…?</em> or <em>Can you…?</em>', ['Give me a map.', 'Help me.', 'I want to pay by card.', 'Say that again.'], ''),
 ('Part F – Extra practice (online): put the dialogue in order', 'a) You\'re welcome. · b) Excuse me, where\'s the museum? · c) Thanks a lot! · d) Go straight on and take the first right. It\'s on the left.', [], ''),
],
'key': [('Part C', '1) Go · 2) Turn · 3) through · 4) Excuse.'),
        ('Part D', '1 the post office · 2 the pharmacy · 3 the library · 4 the station'),
        ('Part E', '1 Can I have a map, please? · 2 Can you help me, please? · 3 Can I pay by card? · 4 Can you say that again, please? (Could is also correct.)'),
        ('Part F', 'b – d – c – a')],
'connect': [('Build polite questions and directions in our Sentence Builder.', '/sentence-builder/', 'Try our Sentence Builder')],
'cefr': 'Writing: I can write simple directions. Spoken Interaction: I can ask for and give directions.',
},
]
