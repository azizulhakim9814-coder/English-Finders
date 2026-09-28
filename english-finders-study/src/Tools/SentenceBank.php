<?php
/**
 * Sentence-order item bank.
 *
 * Unlike the vocabulary tools, word order has no dataset to query — a
 * dictionary row has a definition, not a sentence — so this is a small,
 * hand-curated bank, the same shape as `GrammarBank` and `ReadingBank`. Every
 * item is one sentence, stored as its words in correct order; a round shuffles
 * the *display* order and asks the learner to rebuild the original, so
 * correctness is exact and unambiguous — there is never a second valid
 * ordering to adjudicate.
 *
 * Coverage runs the full A1-C2 range, unlike `GrammarBank`'s deliberate A1-B1
 * cap: what changes with level here is which word-order pattern is being
 * drilled (plain SVO at A1, adverb placement at A2, inversion and cleft
 * sentences from B2 up), not the fairness of the check itself — rebuilding a
 * fixed sequence of words stays unambiguous at any level, the same reasoning
 * `ReadingBank` used to extend past B1.
 *
 * Thirty-six items per level since 1.16.0 (twelve before), so a learner gets
 * more than three ten-sentence sessions per level without repeats. Because
 * the check is an exact match, every sentence must have only ONE acceptable
 * order: the capitalised first word and the punctuation on the last word
 * fix the ends, and 1.16.0 left out adverb positions that native speakers
 * genuinely vary ("I go swimming sometimes on Fridays") rather than mark a
 * natural order wrong. C2 adds "fronting" (Strange as it may seem…, Into the
 * room walked…).
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SentenceBank {
	/**
	 * @return list<array{id:string,topic:string,level:string,words:list<string>,explanation:string}>
	 */
	public static function items(): array {
		return array(
			// --- A1: basic subject-verb-object order -----------------------
			array(
				'id'          => 'a1-1',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'I', 'drink', 'coffee', 'every', 'morning.' ),
				'explanation' => 'A statement follows subject + verb + object + time: "I drink coffee every morning."',
			),
			array(
				'id'          => 'a1-2',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'She', 'likes', 'chocolate', 'cake.' ),
				'explanation' => 'Subject + verb + object: "She likes chocolate cake."',
			),
			array(
				'id'          => 'a1-3',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'We', 'live', 'in', 'a', 'small', 'house.' ),
				'explanation' => 'Subject + verb + place: "We live in a small house."',
			),
			array(
				'id'          => 'a1-4',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'He', 'plays', 'football', 'on', 'Sundays.' ),
				'explanation' => 'Subject + verb + object + time: "He plays football on Sundays."',
			),
			array(
				'id'          => 'a1-5',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'They', 'are', 'watching', 'a', 'movie.' ),
				'explanation' => 'Present continuous: subject + am/is/are + verb-ing + object: "They are watching a movie."',
			),
			array(
				'id'          => 'a1-6',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'My', 'brother', 'works', 'in', 'a', 'bank.' ),
				'explanation' => 'Subject + verb + place: "My brother works in a bank."',
			),
			array(
				'id'          => 'a1-7',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'The', 'children', 'play', 'in', 'the', 'garden.' ),
				'explanation' => 'Subject + verb + place: "The children play in the garden."',
			),
			array(
				'id'          => 'a1-8',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'We', 'eat', 'breakfast', 'at', 'eight', 'o’clock.' ),
				'explanation' => 'Subject + verb + object + time: "We eat breakfast at eight o’clock."',
			),
			array(
				'id'          => 'a1-9',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'My', 'mother', 'cooks', 'dinner', 'every', 'night.' ),
				'explanation' => 'Subject + verb + object + time: "My mother cooks dinner every night."',
			),
			array(
				'id'          => 'a1-10',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'The', 'students', 'study', 'English', 'at', 'school.' ),
				'explanation' => 'Subject + verb + object + place: "The students study English at school."',
			),
			array(
				'id'          => 'a1-11',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'He', 'reads', 'a', 'newspaper', 'every', 'morning.' ),
				'explanation' => 'Subject + verb + object + time: "He reads a newspaper every morning."',
			),
			array(
				'id'          => 'a1-12',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'She', 'writes', 'letters', 'to', 'her', 'friends.' ),
				'explanation' => 'Subject + verb + object + place: "She writes letters to her friends."',
			),

			// --- A2: adverb placement --------------------------------------
			array(
				'id'          => 'a2-1',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'I', 'usually', 'wake', 'up', 'at', 'seven.' ),
				'explanation' => 'Adverbs of frequency (usually, often, always) sit before the main verb: "I usually wake up."',
			),
			array(
				'id'          => 'a2-2',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'She', 'has', 'never', 'been', 'to', 'Paris.' ),
				'explanation' => 'With an auxiliary verb (has), the frequency adverb goes between the auxiliary and the main verb: "has never been".',
			),
			array(
				'id'          => 'a2-3',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'He', 'always', 'arrives', 'late', 'for', 'class.' ),
				'explanation' => '"Always" sits before the main verb "arrives", not before the subject or at the end.',
			),
			array(
				'id'          => 'a2-4',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'We', 'often', 'eat', 'dinner', 'together.' ),
				'explanation' => '"Often" comes before the main verb "eat": "We often eat dinner together."',
			),
			array(
				'id'          => 'a2-5',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'They', 'quickly', 'finished', 'their', 'homework.' ),
				'explanation' => 'A manner adverb like "quickly" usually sits right before the verb it describes.',
			),
			array(
				'id'          => 'a2-6',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'He', 'is', 'always', 'tired', 'after', 'work.' ),
				'explanation' => 'With the verb "be", the frequency adverb goes after it: "is always tired".',
			),
			array(
				'id'          => 'a2-7',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'She', 'rarely', 'eats', 'fast', 'food.' ),
				'explanation' => '"Rarely" sits before the main verb "eats", not before the subject or at the end.',
			),
			array(
				'id'          => 'a2-8',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'They', 'sometimes', 'visit', 'their', 'grandparents.' ),
				'explanation' => '"Sometimes" comes before the main verb "visit".',
			),
			array(
				'id'          => 'a2-9',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'I', 'have', 'already', 'finished', 'my', 'homework.' ),
				'explanation' => 'With an auxiliary verb (have), "already" goes between the auxiliary and the main verb: "have already finished".',
			),
			array(
				'id'          => 'a2-10',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'We', 'hardly', 'ever', 'go', 'to', 'the', 'cinema.' ),
				'explanation' => '"Hardly ever" (almost never) sits before the main verb "go".',
			),
			array(
				'id'          => 'a2-11',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'My', 'father', 'usually', 'drives', 'to', 'work.' ),
				'explanation' => '"Usually" comes before the main verb "drives".',
			),
			array(
				'id'          => 'a2-12',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'She', 'is', 'still', 'waiting', 'for', 'the', 'bus.' ),
				'explanation' => 'With the verb "be" + verb-ing, "still" goes right after "is": "is still waiting".',
			),

			// --- B1: question order, clause order, light inversion --------
			array(
				'id'          => 'b1-1',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Could', 'you', 'tell', 'me', 'where', 'the', 'station', 'is?' ),
				'explanation' => 'An indirect question keeps statement word order after the question word: "where the station is", not "where is the station".',
			),
			array(
				'id'          => 'b1-2',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Although', 'it', 'was', 'raining,', 'we', 'went', 'for', 'a', 'walk.' ),
				'explanation' => 'A subordinate clause introduced by "although" can come first, followed by a comma and the main clause.',
			),
			array(
				'id'          => 'b1-3',
				'topic'       => 'inversion',
				'level'       => 'B1',
				'words'       => array( 'Not', 'only', 'did', 'she', 'win', 'the', 'race,', 'but', 'she', 'also', 'broke', 'the', 'record.' ),
				'explanation' => 'Starting a sentence with "Not only" forces subject-auxiliary inversion: "did she win", not "she did win".',
			),
			array(
				'id'          => 'b1-4',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'I', 'wonder', 'what', 'time', 'the', 'meeting', 'starts.' ),
				'explanation' => 'An embedded question keeps normal statement order: "the meeting starts", not "does the meeting start".',
			),
			array(
				'id'          => 'b1-5',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'By', 'the', 'time', 'we', 'arrived,', 'the', 'film', 'had', 'already', 'started.' ),
				'explanation' => 'A "by the time" clause fronts the earlier event; the main clause after it takes the past perfect: "had already started".',
			),
			array(
				'id'          => 'b1-6',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Do', 'you', 'know', 'what', 'time', 'it', 'is?' ),
				'explanation' => 'An embedded question keeps statement word order: "what time it is", not "what time is it".',
			),
			array(
				'id'          => 'b1-7',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Because', 'she', 'was', 'late,', 'she', 'missed', 'the', 'bus.' ),
				'explanation' => 'A "because" clause can come first, followed by a comma and the main clause.',
			),
			array(
				'id'          => 'b1-8',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'As', 'soon', 'as', 'the', 'rain', 'stopped,', 'we', 'went', 'outside.' ),
				'explanation' => 'An "as soon as" clause can come first, followed by a comma and the main clause.',
			),
			array(
				'id'          => 'b1-9',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'I', 'don’t', 'know', 'why', 'he', 'left', 'so', 'early.' ),
				'explanation' => 'An embedded question keeps normal word order: "why he left", not "why did he leave".',
			),
			array(
				'id'          => 'b1-10',
				'topic'       => 'inversion',
				'level'       => 'B1',
				'words'       => array( 'Hardly', 'had', 'we', 'arrived', 'when', 'it', 'started', 'to', 'rain.' ),
				'explanation' => 'Fronting "hardly" triggers inversion: "had we arrived", followed by "when" for the interrupting event.',
			),
			array(
				'id'          => 'b1-11',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Can', 'you', 'tell', 'me', 'how', 'much', 'this', 'costs?' ),
				'explanation' => 'An indirect question keeps statement order after the question word: "how much this costs", not "how much does this cost".',
			),
			array(
				'id'          => 'b1-12',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'While', 'she', 'was', 'cooking,', 'the', 'phone', 'rang.' ),
				'explanation' => 'A "while" clause describing a background action can come first, followed by a comma and the main clause.',
			),

			// --- B2: inversion, cleft sentences, relative clauses ----------
			array(
				'id'          => 'b2-1',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Rarely', 'have', 'I', 'seen', 'such', 'a', 'beautiful', 'sunset.' ),
				'explanation' => 'A negative adverb ("rarely") fronted for emphasis triggers inversion: "have I seen", not "I have seen".',
			),
			array(
				'id'          => 'b2-2',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'It', 'was', 'her', 'enthusiasm', 'that', 'impressed', 'the', 'interviewers.' ),
				'explanation' => 'An "It was … that …" cleft sentence puts the emphasised element right after "It was".',
			),
			array(
				'id'          => 'b2-3',
				'topic'       => 'relative-clause',
				'level'       => 'B2',
				'words'       => array( 'The', 'book,', 'which', 'I', 'borrowed', 'last', 'week,', 'is', 'due', 'tomorrow.' ),
				'explanation' => 'A non-defining relative clause ("which I borrowed last week") sits between commas, right after the noun it describes.',
			),
			array(
				'id'          => 'b2-4',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Under', 'no', 'circumstances', 'should', 'you', 'share', 'your', 'password.' ),
				'explanation' => 'A fronted negative phrase ("Under no circumstances") triggers inversion: "should you share", not "you should share".',
			),
			array(
				'id'          => 'b2-5',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Little', 'did', 'they', 'know', 'what', 'awaited', 'them.' ),
				'explanation' => 'Fronting "little" for emphasis triggers inversion: "did they know", not "they knew".',
			),
			array(
				'id'          => 'b2-6',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'No', 'sooner', 'had', 'he', 'sat', 'down', 'than', 'the', 'phone', 'rang.' ),
				'explanation' => '"No sooner … than …" triggers inversion in the first clause: "had he sat down".',
			),
			array(
				'id'          => 'b2-7',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Not', 'until', 'she', 'apologised', 'did', 'he', 'forgive', 'her.' ),
				'explanation' => 'Fronting "not until …" triggers inversion in the main clause: "did he forgive her".',
			),
			array(
				'id'          => 'b2-8',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'What', 'surprised', 'me', 'most', 'was', 'his', 'honesty.' ),
				'explanation' => 'A "what … was …" cleft sentence puts the emphasised idea after "was".',
			),
			array(
				'id'          => 'b2-9',
				'topic'       => 'relative-clause',
				'level'       => 'B2',
				'words'       => array( 'The', 'manager,', 'who', 'had', 'worked', 'there', 'for', 'years,', 'resigned', 'suddenly.' ),
				'explanation' => 'A non-defining relative clause ("who had worked there for years") sits between commas, right after the noun it describes.',
			),
			array(
				'id'          => 'b2-10',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Only', 'by', 'working', 'together', 'can', 'we', 'solve', 'this', 'problem.' ),
				'explanation' => 'A fronted "only by …" phrase triggers inversion: "can we solve", not "we can solve".',
			),
			array(
				'id'          => 'b2-11',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'So', 'popular', 'did', 'the', 'film', 'become', 'that', 'it', 'broke', 'box-office', 'records.' ),
				'explanation' => 'Fronting "so + adjective" triggers inversion with "did": "did the film become".',
			),
			array(
				'id'          => 'b2-12',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'It', 'was', 'the', 'manager', 'who', 'approved', 'the', 'budget.' ),
				'explanation' => 'An "It was … who …" cleft sentence puts the emphasised person right after "It was".',
			),

			// --- C1: participle clauses, cleft sentences, fronting --------
			array(
				'id'          => 'c1-1',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Having', 'finished', 'the', 'report,', 'she', 'left', 'the', 'office', 'early.' ),
				'explanation' => 'A perfect participle clause ("Having finished the report") fronts a completed action before the main clause.',
			),
			array(
				'id'          => 'c1-2',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'So', 'exhausted', 'was', 'he', 'that', 'he', 'fell', 'asleep', 'instantly.' ),
				'explanation' => 'Fronting "so + adjective" triggers inversion: "was he", followed by the result clause "that he fell asleep".',
			),
			array(
				'id'          => 'c1-3',
				'topic'       => 'cleft-sentence',
				'level'       => 'C1',
				'words'       => array( 'What', 'we', 'need', 'is', 'a', 'clear', 'plan', 'of', 'action.' ),
				'explanation' => 'A "what … is …" cleft sentence puts the emphasised idea after "is".',
			),
			array(
				'id'          => 'c1-4',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Seldom', 'does', 'one', 'encounter', 'such', 'generosity.' ),
				'explanation' => 'The negative adverb "seldom" fronted for emphasis triggers inversion with "does": "does one encounter".',
			),
			array(
				'id'          => 'c1-5',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Given', 'the', 'circumstances,', 'the', 'decision', 'seems', 'reasonable.' ),
				'explanation' => 'A participle phrase ("Given the circumstances") can open a sentence to set context for the main clause.',
			),
			array(
				'id'          => 'c1-6',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Not', 'having', 'studied', 'enough,', 'he', 'failed', 'the', 'exam.' ),
				'explanation' => 'A negative perfect participle clause ("Not having studied enough") fronts the reason before the main clause.',
			),
			array(
				'id'          => 'c1-7',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Exhausted', 'by', 'the', 'journey,', 'they', 'went', 'straight', 'to', 'bed.' ),
				'explanation' => 'A past participle clause ("Exhausted by the journey") fronts the cause of the state before the main clause.',
			),
			array(
				'id'          => 'c1-8',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Only', 'when', 'the', 'results', 'arrived', 'did', 'she', 'relax.' ),
				'explanation' => 'A fronted "only when …" clause triggers inversion in the main clause: "did she relax".',
			),
			array(
				'id'          => 'c1-9',
				'topic'       => 'cleft-sentence',
				'level'       => 'C1',
				'words'       => array( 'All', 'she', 'wanted', 'was', 'a', 'quiet', 'evening', 'at', 'home.' ),
				'explanation' => 'An "all … was …" cleft sentence emphasises the one thing wanted, placed after "was".',
			),
			array(
				'id'          => 'c1-10',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Not', 'for', 'a', 'moment', 'did', 'he', 'doubt', 'her', 'word.' ),
				'explanation' => 'Fronting "not for a moment" for emphasis triggers inversion: "did he doubt".',
			),
			array(
				'id'          => 'c1-11',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Weighing', 'the', 'risks', 'carefully,', 'the', 'board', 'rejected', 'the', 'proposal.' ),
				'explanation' => 'A present participle clause ("Weighing the risks carefully") fronts the action leading up to the main clause.',
			),
			array(
				'id'          => 'c1-12',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Such', 'is', 'his', 'reputation', 'that', 'clients', 'trust', 'him', 'instantly.' ),
				'explanation' => 'Fronting "such" triggers inversion: "is his reputation", followed by the result clause "that clients trust him instantly".',
			),

			// --- C2: advanced inversion, subjunctive -----------------------
			array(
				'id'          => 'c2-1',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Were', 'I', 'in', 'your', 'position,', 'I', 'would', 'accept', 'the', 'offer.' ),
				'explanation' => 'A formal conditional can drop "if" and invert instead: "Were I in your position" means "If I were in your position".',
			),
			array(
				'id'          => 'c2-2',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Such', 'was', 'the', 'noise', 'that', 'no', 'one', 'could', 'sleep.' ),
				'explanation' => 'Fronting "such" for emphasis triggers inversion: "was the noise", followed by the result clause "that no one could sleep".',
			),
			array(
				'id'          => 'c2-3',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Only', 'after', 'the', 'meeting', 'ended', 'did', 'the', 'truth', 'come', 'out.' ),
				'explanation' => 'A fronted "only after …" time clause triggers inversion in the main clause: "did the truth come out".',
			),
			array(
				'id'          => 'c2-4',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Little', 'does', 'he', 'realize', 'how', 'much', 'this', 'means', 'to', 'us.' ),
				'explanation' => 'Fronting "little" with a present-tense main verb needs "does" for inversion: "does he realize".',
			),
			array(
				'id'          => 'c2-5',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Never', 'before', 'had', 'the', 'team', 'faced', 'such', 'a', 'challenge.' ),
				'explanation' => 'Fronting "never before" with a past perfect triggers inversion: "had the team faced".',
			),
			array(
				'id'          => 'c2-6',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Had', 'she', 'known', 'the', 'truth,', 'she', 'would', 'have', 'acted', 'differently.' ),
				'explanation' => 'A formal past conditional can drop "if" and invert instead: "Had she known" means "If she had known".',
			),
			array(
				'id'          => 'c2-7',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Should', 'you', 'need', 'any', 'help,', 'do', 'not', 'hesitate', 'to', 'call.' ),
				'explanation' => 'A formal conditional can drop "if" and invert instead: "Should you need" means "If you should need".',
			),
			array(
				'id'          => 'c2-8',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Not', 'since', 'the', 'war', 'had', 'the', 'country', 'faced', 'such', 'hardship.' ),
				'explanation' => 'Fronting "not since …" triggers inversion: "had the country faced".',
			),
			array(
				'id'          => 'c2-9',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'So', 'rarely', 'does', 'he', 'complain', 'that', 'his', 'silence', 'worried', 'us.' ),
				'explanation' => 'Fronting "so rarely" triggers inversion with "does": "does he complain".',
			),
			array(
				'id'          => 'c2-10',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Nowhere', 'else', 'could', 'such', 'beauty', 'be', 'found.' ),
				'explanation' => 'Fronting the negative-like adverb "nowhere else" triggers inversion: "could such beauty be found".',
			),
			array(
				'id'          => 'c2-11',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Not', 'a', 'single', 'word', 'did', 'she', 'say', 'during', 'the', 'meeting.' ),
				'explanation' => 'Fronting "not a single word" for emphasis triggers inversion: "did she say".',
			),
			array(
				'id'          => 'c2-12',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Were', 'it', 'not', 'for', 'your', 'help,', 'we', 'would', 'have', 'failed.' ),
				'explanation' => 'A formal conditional using "were it not for" inverts instead of using "if it were not for".',
			),

			// --- 1.16.0 expansion: A1  --------------------
			array(
				'id'          => 'a1-13',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'My', 'sister', 'has', 'a', 'black', 'cat.' ),
				'explanation' => 'Subject + verb + object, with the adjective before the noun: "My sister has a black cat."',
			),
			array(
				'id'          => 'a1-14',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'We', 'go', 'to', 'the', 'park', 'on', 'Saturdays.' ),
				'explanation' => 'Subject + verb + place + time: "We go to the park on Saturdays."',
			),
			array(
				'id'          => 'a1-15',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'The', 'shop', 'opens', 'at', 'nine', 'o’clock.' ),
				'explanation' => 'Subject + verb + time: "The shop opens at nine o’clock."',
			),
			array(
				'id'          => 'a1-16',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'I', 'have', 'two', 'brothers', 'and', 'one', 'sister.' ),
				'explanation' => 'Subject + verb + object, with "and" joining the two objects: "I have two brothers and one sister."',
			),
			array(
				'id'          => 'a1-17',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'He', 'is', 'my', 'best', 'friend.' ),
				'explanation' => 'Subject + "be" + noun phrase: "He is my best friend."',
			),
			array(
				'id'          => 'a1-18',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'They', 'live', 'in', 'a', 'big', 'city.' ),
				'explanation' => 'Subject + verb + place: "They live in a big city."',
			),
			array(
				'id'          => 'a1-19',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'She', 'drinks', 'milk', 'every', 'day.' ),
				'explanation' => 'Subject + verb + object + time; the object comes before the time phrase: "She drinks milk every day."',
			),
			array(
				'id'          => 'a1-20',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'My', 'father', 'drives', 'a', 'blue', 'car.' ),
				'explanation' => 'Subject + verb + object, with the adjective before the noun: "My father drives a blue car."',
			),
			array(
				'id'          => 'a1-21',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'We', 'have', 'English', 'lessons', 'on', 'Monday.' ),
				'explanation' => 'Subject + verb + object + time: "We have English lessons on Monday."',
			),
			array(
				'id'          => 'a1-22',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'The', 'baby', 'is', 'sleeping', 'now.' ),
				'explanation' => 'Subject + present continuous verb + time: "The baby is sleeping now."',
			),
			array(
				'id'          => 'a1-23',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'I', 'like', 'apples', 'and', 'bananas.' ),
				'explanation' => 'Subject + verb + object, with "and" joining the two objects: "I like apples and bananas."',
			),
			array(
				'id'          => 'a1-24',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'Tom', 'walks', 'to', 'school', 'every', 'morning.' ),
				'explanation' => 'Subject + verb + place + time: "Tom walks to school every morning."',
			),
			array(
				'id'          => 'a1-25',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'Our', 'teacher', 'is', 'very', 'kind.' ),
				'explanation' => 'Subject + "be" + adjective, with "very" before the adjective: "Our teacher is very kind."',
			),
			array(
				'id'          => 'a1-26',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'The', 'dog', 'is', 'under', 'the', 'table.' ),
				'explanation' => 'Subject + "be" + place: "The dog is under the table."',
			),
			array(
				'id'          => 'a1-27',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'I', 'am', 'reading', 'a', 'good', 'book.' ),
				'explanation' => 'Subject + present continuous verb + object: "I am reading a good book."',
			),
			array(
				'id'          => 'a1-28',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'She', 'wants', 'a', 'new', 'phone.' ),
				'explanation' => 'Subject + verb + object, with the adjective before the noun: "She wants a new phone."',
			),
			array(
				'id'          => 'a1-29',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'My', 'grandparents', 'live', 'in', 'the', 'country.' ),
				'explanation' => 'Subject + verb + place: "My grandparents live in the country."',
			),
			array(
				'id'          => 'a1-30',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'We', 'are', 'hungry', 'after', 'school.' ),
				'explanation' => 'Subject + "be" + adjective + time: "We are hungry after school."',
			),
			array(
				'id'          => 'a1-31',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'He', 'cleans', 'his', 'room', 'on', 'Sundays.' ),
				'explanation' => 'Subject + verb + object + time: "He cleans his room on Sundays."',
			),
			array(
				'id'          => 'a1-32',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'The', 'children', 'are', 'playing', 'football.' ),
				'explanation' => 'Subject + present continuous verb + object: "The children are playing football."',
			),
			array(
				'id'          => 'a1-33',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'I', 'get', 'up', 'at', 'seven', 'o’clock.' ),
				'explanation' => 'Subject + phrasal verb + time: "I get up at seven o’clock."',
			),
			array(
				'id'          => 'a1-34',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'My', 'mother', 'works', 'in', 'a', 'hospital.' ),
				'explanation' => 'Subject + verb + place: "My mother works in a hospital."',
			),
			array(
				'id'          => 'a1-35',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'This', 'is', 'my', 'favourite', 'song.' ),
				'explanation' => '"This" + "be" + noun phrase: "This is my favourite song."',
			),
			array(
				'id'          => 'a1-36',
				'topic'       => 'word-order',
				'level'       => 'A1',
				'words'       => array( 'They', 'visit', 'their', 'grandmother', 'every', 'week.' ),
				'explanation' => 'Subject + verb + object + time: "They visit their grandmother every week."',
			),

			// --- 1.16.0 expansion: A2  --------------------
			array(
				'id'          => 'a2-13',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'She', 'always', 'does', 'her', 'homework', 'after', 'dinner.' ),
				'explanation' => 'Frequency adverbs go before the main verb: "She always does her homework after dinner."',
			),
			array(
				'id'          => 'a2-14',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'We', 'never', 'eat', 'meat', 'at', 'home.' ),
				'explanation' => '"Never" goes before the main verb: "We never eat meat at home."',
			),
			array(
				'id'          => 'a2-15',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'He', 'is', 'often', 'late', 'for', 'work.' ),
				'explanation' => 'Frequency adverbs go after "be": "He is often late for work."',
			),
			array(
				'id'          => 'a2-16',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'I', 'sometimes', 'forget', 'my', 'keys.' ),
				'explanation' => '"Sometimes" goes before the main verb: "I sometimes forget my keys."',
			),
			array(
				'id'          => 'a2-17',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'They', 'have', 'just', 'arrived', 'at', 'the', 'airport.' ),
				'explanation' => '"Just" goes between "have" and the past participle: "They have just arrived at the airport."',
			),
			array(
				'id'          => 'a2-18',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'My', 'brother', 'usually', 'plays', 'tennis.' ),
				'explanation' => '"Usually" goes before the main verb: "My brother usually plays tennis."',
			),
			array(
				'id'          => 'a2-19',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'She', 'has', 'already', 'read', 'this', 'book.' ),
				'explanation' => '"Already" goes between "has" and the past participle: "She has already read this book."',
			),
			array(
				'id'          => 'a2-20',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'I', 'have', 'never', 'seen', 'snow.' ),
				'explanation' => '"Never" goes between "have" and the past participle: "I have never seen snow."',
			),
			array(
				'id'          => 'a2-21',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'We', 'are', 'usually', 'very', 'busy.' ),
				'explanation' => 'Frequency adverbs go after "be": "We are usually very busy."',
			),
			array(
				'id'          => 'a2-22',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'He', 'hardly', 'ever', 'watches', 'television.' ),
				'explanation' => '"Hardly ever" goes before the main verb: "He hardly ever watches television."',
			),
			array(
				'id'          => 'a2-23',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'You', 'should', 'always', 'wear', 'a', 'seatbelt.' ),
				'explanation' => 'With a modal verb, the adverb goes after the modal: "You should always wear a seatbelt."',
			),
			array(
				'id'          => 'a2-24',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'They', 'often', 'go', 'to', 'the', 'beach.' ),
				'explanation' => '"Often" goes before the main verb: "They often go to the beach."',
			),
			array(
				'id'          => 'a2-25',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'I', 'will', 'probably', 'see', 'you', 'tomorrow.' ),
				'explanation' => '"Probably" goes after "will" and before the main verb: "I will probably see you tomorrow."',
			),
			array(
				'id'          => 'a2-26',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'She', 'speaks', 'English', 'very', 'well.' ),
				'explanation' => 'An adverb of manner goes after the object, not between the verb and the object: "She speaks English very well."',
			),
			array(
				'id'          => 'a2-27',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'He', 'is', 'always', 'happy', 'to', 'help.' ),
				'explanation' => 'Frequency adverbs go after "be": "He is always happy to help."',
			),
			array(
				'id'          => 'a2-28',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'We', 'have', 'already', 'booked', 'our', 'tickets.' ),
				'explanation' => '"Already" goes between "have" and the past participle: "We have already booked our tickets."',
			),
			array(
				'id'          => 'a2-29',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'My', 'parents', 'rarely', 'watch', 'films.' ),
				'explanation' => '"Rarely" goes before the main verb: "My parents rarely watch films."',
			),
			array(
				'id'          => 'a2-30',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'The', 'bus', 'is', 'never', 'on', 'time.' ),
				'explanation' => '"Never" goes after "be": "The bus is never on time."',
			),
			array(
				'id'          => 'a2-31',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'I', 'still', 'live', 'with', 'my', 'parents.' ),
				'explanation' => '"Still" goes before the main verb: "I still live with my parents."',
			),
			array(
				'id'          => 'a2-32',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'She', 'has', 'recently', 'started', 'a', 'new', 'job.' ),
				'explanation' => '"Recently" can go between "has" and the past participle: "She has recently started a new job."',
			),
			array(
				'id'          => 'a2-33',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'They', 'are', 'always', 'arguing', 'about', 'money.' ),
				'explanation' => 'In the continuous, the adverb goes after "be": "They are always arguing about money."',
			),
			array(
				'id'          => 'a2-34',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'I', 'usually', 'walk', 'to', 'work.' ),
				'explanation' => '"Usually" goes before the main verb: "I usually walk to work."',
			),
			array(
				'id'          => 'a2-35',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'He', 'has', 'never', 'been', 'abroad.' ),
				'explanation' => '"Never" goes between "has" and the past participle: "He has never been abroad."',
			),
			array(
				'id'          => 'a2-36',
				'topic'       => 'adverb-placement',
				'level'       => 'A2',
				'words'       => array( 'We', 'sometimes', 'order', 'pizza.' ),
				'explanation' => '"Sometimes" goes before the main verb: "We sometimes order pizza."',
			),

			// --- 1.16.0 expansion: B1  --------------------
			array(
				'id'          => 'b1-13',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Can', 'you', 'tell', 'me', 'where', 'the', 'bank', 'is?' ),
				'explanation' => 'In an indirect question, the subject comes before the verb: "where the bank is", not "where is the bank".',
			),
			array(
				'id'          => 'b1-14',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'I', 'don’t', 'know', 'what', 'she', 'wants.' ),
				'explanation' => 'After "I don’t know", use statement order: "what she wants".',
			),
			array(
				'id'          => 'b1-15',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Do', 'you', 'know', 'when', 'the', 'train', 'leaves?' ),
				'explanation' => 'The indirect question uses statement order without "does": "when the train leaves".',
			),
			array(
				'id'          => 'b1-16',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Could', 'you', 'tell', 'me', 'how', 'old', 'he', 'is?' ),
				'explanation' => 'In an indirect question, the verb goes after the subject: "how old he is".',
			),
			array(
				'id'          => 'b1-17',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'I', 'wonder', 'why', 'they', 'didn’t', 'come.' ),
				'explanation' => 'After "I wonder", use statement order: "why they didn’t come".',
			),
			array(
				'id'          => 'b1-18',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Do', 'you', 'remember', 'where', 'you', 'parked', 'the', 'car?' ),
				'explanation' => 'The indirect question uses statement order: "where you parked the car".',
			),
			array(
				'id'          => 'b1-19',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Although', 'he', 'was', 'tired,', 'he', 'finished', 'the', 'race.' ),
				'explanation' => 'The "although" clause comes first and is followed by a comma: "Although he was tired, he finished the race."',
			),
			array(
				'id'          => 'b1-20',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'If', 'it', 'rains', 'tomorrow,', 'we', 'will', 'stay', 'at', 'home.' ),
				'explanation' => 'An "if" clause at the start is followed by a comma and the main clause: "If it rains tomorrow, we will stay at home."',
			),
			array(
				'id'          => 'b1-21',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'When', 'I', 'got', 'home,', 'my', 'sister', 'was', 'watching', 'TV.' ),
				'explanation' => 'The time clause comes first, then the main clause: "When I got home, my sister was watching TV."',
			),
			array(
				'id'          => 'b1-22',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Before', 'she', 'went', 'to', 'bed,', 'she', 'brushed', 'her', 'teeth.' ),
				'explanation' => 'The "before" clause comes first, followed by a comma: "Before she went to bed, she brushed her teeth."',
			),
			array(
				'id'          => 'b1-23',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'After', 'we', 'had', 'eaten,', 'we', 'went', 'for', 'a', 'walk.' ),
				'explanation' => 'The "after" clause uses the past perfect for the earlier action: "After we had eaten, we went for a walk."',
			),
			array(
				'id'          => 'b1-24',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Unless', 'you', 'hurry,', 'you', 'will', 'miss', 'the', 'bus.' ),
				'explanation' => 'The "unless" clause comes first, followed by the result: "Unless you hurry, you will miss the bus."',
			),
			array(
				'id'          => 'b1-25',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Because', 'it', 'was', 'cold,', 'we', 'stayed', 'inside.' ),
				'explanation' => 'The reason clause comes first, followed by a comma: "Because it was cold, we stayed inside."',
			),
			array(
				'id'          => 'b1-26',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'As', 'soon', 'as', 'I', 'finish', 'work,', 'I', 'will', 'call', 'you.' ),
				'explanation' => '"As soon as" + present, then the future main clause: "As soon as I finish work, I will call you."',
			),
			array(
				'id'          => 'b1-27',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'I', 'asked', 'him', 'where', 'he', 'lived.' ),
				'explanation' => 'A reported question uses statement order: "where he lived".',
			),
			array(
				'id'          => 'b1-28',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'She', 'asked', 'me', 'if', 'I', 'liked', 'coffee.' ),
				'explanation' => 'A reported yes/no question uses "if" + statement order: "if I liked coffee".',
			),
			array(
				'id'          => 'b1-29',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Do', 'you', 'have', 'any', 'idea', 'who', 'that', 'man', 'is?' ),
				'explanation' => 'The indirect question puts the verb last: "who that man is".',
			),
			array(
				'id'          => 'b1-30',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'While', 'we', 'were', 'waiting,', 'it', 'started', 'to', 'snow.' ),
				'explanation' => 'The "while" clause comes first, followed by a comma: "While we were waiting, it started to snow."',
			),
			array(
				'id'          => 'b1-31',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Since', 'you', 'are', 'here,', 'you', 'can', 'help', 'me.' ),
				'explanation' => '"Since" giving a reason starts the sentence, followed by a comma: "Since you are here, you can help me."',
			),
			array(
				'id'          => 'b1-32',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'I’m', 'not', 'sure', 'whether', 'she', 'will', 'come.' ),
				'explanation' => 'After "I’m not sure whether", use statement order: "whether she will come".',
			),
			array(
				'id'          => 'b1-33',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Even', 'though', 'it', 'was', 'expensive,', 'she', 'bought', 'the', 'dress.' ),
				'explanation' => 'The "even though" clause comes first, followed by a comma: "Even though it was expensive, she bought the dress."',
			),
			array(
				'id'          => 'b1-34',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'If', 'I', 'were', 'you,', 'I', 'would', 'apologise.' ),
				'explanation' => 'A second-conditional "if" clause comes first: "If I were you, I would apologise."',
			),
			array(
				'id'          => 'b1-35',
				'topic'       => 'question-order',
				'level'       => 'B1',
				'words'       => array( 'Could', 'you', 'explain', 'what', 'this', 'word', 'means?' ),
				'explanation' => 'The indirect question uses statement order without "does": "what this word means".',
			),
			array(
				'id'          => 'b1-36',
				'topic'       => 'clause-order',
				'level'       => 'B1',
				'words'       => array( 'Once', 'the', 'guests', 'arrived,', 'the', 'party', 'began.' ),
				'explanation' => 'The "once" clause comes first, followed by a comma: "Once the guests arrived, the party began."',
			),

			// --- 1.16.0 expansion: B2  --------------------
			array(
				'id'          => 'b2-13',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Never', 'have', 'I', 'felt', 'so', 'nervous.' ),
				'explanation' => 'After "Never" at the start, the auxiliary comes before the subject: "Never have I felt so nervous."',
			),
			array(
				'id'          => 'b2-14',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Seldom', 'do', 'we', 'see', 'such', 'talent.' ),
				'explanation' => 'After "Seldom", use "do" + subject + verb: "Seldom do we see such talent."',
			),
			array(
				'id'          => 'b2-15',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Not', 'only', 'is', 'she', 'clever,', 'but', 'she', 'is', 'also', 'kind.' ),
				'explanation' => '"Not only" at the start inverts the first clause; the second uses "but … also": "Not only is she clever, but she is also kind."',
			),
			array(
				'id'          => 'b2-16',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'It', 'was', 'in', 'Paris', 'that', 'they', 'first', 'met.' ),
				'explanation' => 'An "it was … that" cleft puts the focus on the place: "It was in Paris that they first met."',
			),
			array(
				'id'          => 'b2-17',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'What', 'I', 'need', 'is', 'a', 'long', 'holiday.' ),
				'explanation' => 'A "what" cleft: "What I need" + "is" + the focus: "What I need is a long holiday."',
			),
			array(
				'id'          => 'b2-18',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'It', 'is', 'the', 'price', 'that', 'worries', 'me.' ),
				'explanation' => 'An "it is … that" cleft puts the focus on "the price": "It is the price that worries me."',
			),
			array(
				'id'          => 'b2-19',
				'topic'       => 'relative-clause',
				'level'       => 'B2',
				'words'       => array( 'The', 'woman', 'who', 'lives', 'next', 'door', 'is', 'a', 'doctor.' ),
				'explanation' => 'The relative clause follows the noun it describes: "The woman who lives next door is a doctor."',
			),
			array(
				'id'          => 'b2-20',
				'topic'       => 'relative-clause',
				'level'       => 'B2',
				'words'       => array( 'The', 'car,', 'which', 'was', 'brand', 'new,', 'broke', 'down.' ),
				'explanation' => 'A non-defining clause sits between commas after the noun: "The car, which was brand new, broke down."',
			),
			array(
				'id'          => 'b2-21',
				'topic'       => 'relative-clause',
				'level'       => 'B2',
				'words'       => array( 'My', 'brother,', 'whose', 'wife', 'is', 'Spanish,', 'lives', 'in', 'Madrid.' ),
				'explanation' => '"Whose" + noun introduces the extra information: "My brother, whose wife is Spanish, lives in Madrid."',
			),
			array(
				'id'          => 'b2-22',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Hardly', 'had', 'she', 'left', 'when', 'the', 'phone', 'rang.' ),
				'explanation' => '"Hardly" + "had" + subject + past participle, then "when": "Hardly had she left when the phone rang."',
			),
			array(
				'id'          => 'b2-23',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'On', 'no', 'account', 'should', 'you', 'open', 'this', 'door.' ),
				'explanation' => '"On no account" at the start inverts the modal and subject: "On no account should you open this door."',
			),
			array(
				'id'          => 'b2-24',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Only', 'then', 'did', 'I', 'understand', 'the', 'problem.' ),
				'explanation' => '"Only then" at the start inverts with "did": "Only then did I understand the problem."',
			),
			array(
				'id'          => 'b2-25',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Not', 'until', 'later', 'did', 'we', 'learn', 'the', 'truth.' ),
				'explanation' => '"Not until" at the start inverts the main clause: "Not until later did we learn the truth."',
			),
			array(
				'id'          => 'b2-26',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'All', 'I', 'want', 'is', 'some', 'peace', 'and', 'quiet.' ),
				'explanation' => '"All I want" + "is" + the focus: "All I want is some peace and quiet."',
			),
			array(
				'id'          => 'b2-27',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'It', 'was', 'my', 'sister', 'who', 'found', 'the', 'keys.' ),
				'explanation' => 'An "it was … who" cleft puts the focus on a person: "It was my sister who found the keys."',
			),
			array(
				'id'          => 'b2-28',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'The', 'reason', 'I', 'called', 'was', 'to', 'apologise.' ),
				'explanation' => '"The reason" + clause + "was" + the focus: "The reason I called was to apologise."',
			),
			array(
				'id'          => 'b2-29',
				'topic'       => 'relative-clause',
				'level'       => 'B2',
				'words'       => array( 'The', 'hotel', 'where', 'we', 'stayed', 'was', 'very', 'comfortable.' ),
				'explanation' => '"Where" introduces a clause about the place, before the main verb: "The hotel where we stayed was very comfortable."',
			),
			array(
				'id'          => 'b2-30',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Rarely', 'does', 'it', 'snow', 'in', 'this', 'city.' ),
				'explanation' => 'After "Rarely", use "does" + subject + verb: "Rarely does it snow in this city."',
			),
			array(
				'id'          => 'b2-31',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'What', 'annoys', 'me', 'is', 'his', 'constant', 'complaining.' ),
				'explanation' => 'A "what" cleft: "What annoys me" + "is" + the focus: "What annoys me is his constant complaining."',
			),
			array(
				'id'          => 'b2-32',
				'topic'       => 'cleft-sentence',
				'level'       => 'B2',
				'words'       => array( 'It', 'wasn’t', 'until', 'midnight', 'that', 'they', 'arrived.' ),
				'explanation' => '"It wasn’t until … that" stresses the time: "It wasn’t until midnight that they arrived."',
			),
			array(
				'id'          => 'b2-33',
				'topic'       => 'relative-clause',
				'level'       => 'B2',
				'words'       => array( 'The', 'people', 'we', 'met', 'on', 'holiday', 'were', 'very', 'friendly.' ),
				'explanation' => 'The relative pronoun can be left out when it is the object: "The people we met on holiday were very friendly."',
			),
			array(
				'id'          => 'b2-34',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'So', 'tired', 'was', 'she', 'that', 'she', 'fell', 'asleep.' ),
				'explanation' => '"So" + adjective at the start, then inversion and "that": "So tired was she that she fell asleep."',
			),
			array(
				'id'          => 'b2-35',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Such', 'was', 'his', 'anger', 'that', 'he', 'left', 'the', 'room.' ),
				'explanation' => '"Such" + "be" + subject, then "that": "Such was his anger that he left the room."',
			),
			array(
				'id'          => 'b2-36',
				'topic'       => 'inversion',
				'level'       => 'B2',
				'words'       => array( 'Scarcely', 'had', 'the', 'match', 'begun', 'when', 'it', 'started', 'raining.' ),
				'explanation' => '"Scarcely" + "had" + subject + past participle, then "when": "Scarcely had the match begun when it started raining."',
			),

			// --- 1.16.0 expansion: C1  --------------------
			array(
				'id'          => 'c1-13',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Having', 'lived', 'abroad', 'for', 'years,', 'he', 'spoke', 'three', 'languages.' ),
				'explanation' => 'A perfect participle clause comes first, followed by the main clause: "Having lived abroad for years, he spoke three languages."',
			),
			array(
				'id'          => 'c1-14',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Left', 'alone,', 'the', 'child', 'began', 'to', 'cry.' ),
				'explanation' => 'A past participle clause describes the subject that follows: "Left alone, the child began to cry."',
			),
			array(
				'id'          => 'c1-15',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Walking', 'along', 'the', 'beach,', 'we', 'found', 'a', 'message', 'in', 'a', 'bottle.' ),
				'explanation' => 'A present participle clause comes first, then the main clause: "Walking along the beach, we found a message in a bottle."',
			),
			array(
				'id'          => 'c1-16',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Built', 'in', '1850,', 'the', 'bridge', 'is', 'still', 'in', 'use.' ),
				'explanation' => 'A passive participle clause describes the subject that follows: "Built in 1850, the bridge is still in use."',
			),
			array(
				'id'          => 'c1-17',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Not', 'knowing', 'what', 'to', 'say,', 'she', 'remained', 'silent.' ),
				'explanation' => '"Not" goes before the participle: "Not knowing what to say, she remained silent."',
			),
			array(
				'id'          => 'c1-18',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Having', 'been', 'warned', 'twice,', 'he', 'was', 'finally', 'dismissed.' ),
				'explanation' => 'A perfect passive participle clause comes first: "Having been warned twice, he was finally dismissed."',
			),
			array(
				'id'          => 'c1-19',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Feeling', 'unwell,', 'she', 'went', 'home', 'early.' ),
				'explanation' => 'A participle clause giving the reason comes first: "Feeling unwell, she went home early."',
			),
			array(
				'id'          => 'c1-20',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Surrounded', 'by', 'mountains,', 'the', 'village', 'is', 'hard', 'to', 'reach.' ),
				'explanation' => 'A past participle clause describes the subject that follows: "Surrounded by mountains, the village is hard to reach."',
			),
			array(
				'id'          => 'c1-21',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Never', 'again', 'will', 'I', 'trust', 'him.' ),
				'explanation' => '"Never again" at the start inverts the auxiliary and subject: "Never again will I trust him."',
			),
			array(
				'id'          => 'c1-22',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Only', 'by', 'chance', 'did', 'we', 'discover', 'the', 'error.' ),
				'explanation' => '"Only by chance" at the start inverts with "did": "Only by chance did we discover the error."',
			),
			array(
				'id'          => 'c1-23',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'In', 'no', 'way', 'is', 'this', 'your', 'fault.' ),
				'explanation' => '"In no way" at the start inverts "be" and the subject: "In no way is this your fault."',
			),
			array(
				'id'          => 'c1-24',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'At', 'no', 'point', 'did', 'anyone', 'mention', 'the', 'cost.' ),
				'explanation' => '"At no point" at the start inverts with "did": "At no point did anyone mention the cost."',
			),
			array(
				'id'          => 'c1-25',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Nowhere', 'is', 'the', 'problem', 'more', 'obvious', 'than', 'here.' ),
				'explanation' => '"Nowhere" at the start inverts "be" and the subject: "Nowhere is the problem more obvious than here."',
			),
			array(
				'id'          => 'c1-26',
				'topic'       => 'subjunctive',
				'level'       => 'C1',
				'words'       => array( 'Were', 'we', 'to', 'leave', 'now,', 'we', 'would', 'arrive', 'on', 'time.' ),
				'explanation' => 'A formal conditional inverts "were" + subject + to-infinitive: "Were we to leave now, we would arrive on time."',
			),
			array(
				'id'          => 'c1-27',
				'topic'       => 'cleft-sentence',
				'level'       => 'C1',
				'words'       => array( 'What', 'worries', 'me', 'is', 'the', 'lack', 'of', 'evidence.' ),
				'explanation' => 'A "what" cleft: "What worries me" + "is" + the focus: "What worries me is the lack of evidence."',
			),
			array(
				'id'          => 'c1-28',
				'topic'       => 'cleft-sentence',
				'level'       => 'C1',
				'words'       => array( 'It', 'was', 'only', 'later', 'that', 'I', 'realised', 'my', 'mistake.' ),
				'explanation' => '"It was only later that" stresses the time: "It was only later that I realised my mistake."',
			),
			array(
				'id'          => 'c1-29',
				'topic'       => 'cleft-sentence',
				'level'       => 'C1',
				'words'       => array( 'The', 'thing', 'that', 'impressed', 'me', 'most', 'was', 'her', 'patience.' ),
				'explanation' => '"The thing that …" + "was" + the focus: "The thing that impressed me most was her patience."',
			),
			array(
				'id'          => 'c1-30',
				'topic'       => 'cleft-sentence',
				'level'       => 'C1',
				'words'       => array( 'What', 'she', 'did', 'next', 'surprised', 'everyone.' ),
				'explanation' => 'A "what" clause acts as the subject: "What she did next surprised everyone."',
			),
			array(
				'id'          => 'c1-31',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Having', 'considered', 'all', 'the', 'options,', 'the', 'committee', 'made', 'its', 'decision.' ),
				'explanation' => 'A perfect participle clause comes first: "Having considered all the options, the committee made its decision."',
			),
			array(
				'id'          => 'c1-32',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Asked', 'about', 'the', 'delay,', 'the', 'manager', 'refused', 'to', 'comment.' ),
				'explanation' => 'A passive participle clause describes the subject that follows: "Asked about the delay, the manager refused to comment."',
			),
			array(
				'id'          => 'c1-33',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'So', 'complex', 'was', 'the', 'problem', 'that', 'nobody', 'could', 'solve', 'it.' ),
				'explanation' => '"So" + adjective + "be" + subject, then "that": "So complex was the problem that nobody could solve it."',
			),
			array(
				'id'          => 'c1-34',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Little', 'did', 'we', 'suspect', 'that', 'he', 'was', 'lying.' ),
				'explanation' => '"Little" at the start inverts with "did": "Little did we suspect that he was lying."',
			),
			array(
				'id'          => 'c1-35',
				'topic'       => 'participle-clause',
				'level'       => 'C1',
				'words'       => array( 'Given', 'the', 'time', 'available,', 'we', 'did', 'remarkably', 'well.' ),
				'explanation' => '"Given" + noun phrase opens the sentence: "Given the time available, we did remarkably well."',
			),
			array(
				'id'          => 'c1-36',
				'topic'       => 'inversion',
				'level'       => 'C1',
				'words'       => array( 'Only', 'after', 'years', 'of', 'practice', 'did', 'she', 'master', 'the', 'violin.' ),
				'explanation' => '"Only after …" at the start inverts the main clause: "Only after years of practice did she master the violin."',
			),

			// --- 1.16.0 expansion: C2  --------------------
			array(
				'id'          => 'c2-13',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Had', 'I', 'known', 'about', 'the', 'traffic,', 'I', 'would', 'have', 'left', 'earlier.' ),
				'explanation' => 'An inverted third conditional drops "if": "Had I known about the traffic, I would have left earlier."',
			),
			array(
				'id'          => 'c2-14',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Should', 'the', 'situation', 'change,', 'we', 'will', 'inform', 'you', 'immediately.' ),
				'explanation' => '"Should" + subject replaces "if": "Should the situation change, we will inform you immediately."',
			),
			array(
				'id'          => 'c2-15',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Were', 'the', 'government', 'to', 'raise', 'taxes,', 'there', 'would', 'be', 'protests.' ),
				'explanation' => '"Were" + subject + to-infinitive replaces "if": "Were the government to raise taxes, there would be protests."',
			),
			array(
				'id'          => 'c2-16',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Not', 'once', 'did', 'she', 'complain', 'about', 'the', 'conditions.' ),
				'explanation' => '"Not once" at the start inverts with "did": "Not once did she complain about the conditions."',
			),
			array(
				'id'          => 'c2-17',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Only', 'rarely', 'do', 'such', 'opportunities', 'arise.' ),
				'explanation' => '"Only rarely" at the start inverts with "do": "Only rarely do such opportunities arise."',
			),
			array(
				'id'          => 'c2-18',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Under', 'no', 'circumstances', 'are', 'visitors', 'allowed', 'to', 'enter.' ),
				'explanation' => '"Under no circumstances" at the start inverts "be" and the subject: "Under no circumstances are visitors allowed to enter."',
			),
			array(
				'id'          => 'c2-19',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'So', 'convincing', 'was', 'his', 'argument', 'that', 'nobody', 'objected.' ),
				'explanation' => '"So" + adjective + "be" + subject, then "that": "So convincing was his argument that nobody objected."',
			),
			array(
				'id'          => 'c2-20',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Such', 'was', 'the', 'demand', 'that', 'tickets', 'sold', 'out', 'in', 'minutes.' ),
				'explanation' => '"Such" + "be" + subject, then "that": "Such was the demand that tickets sold out in minutes."',
			),
			array(
				'id'          => 'c2-21',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Rarely,', 'if', 'ever,', 'does', 'he', 'admit', 'his', 'mistakes.' ),
				'explanation' => '"Rarely, if ever," at the start inverts with "does": "Rarely, if ever, does he admit his mistakes."',
			),
			array(
				'id'          => 'c2-22',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Not', 'until', 'the', 'final', 'whistle', 'did', 'the', 'crowd', 'relax.' ),
				'explanation' => '"Not until" + time at the start inverts the main clause: "Not until the final whistle did the crowd relax."',
			),
			array(
				'id'          => 'c2-23',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Nowhere', 'in', 'the', 'report', 'is', 'this', 'issue', 'mentioned.' ),
				'explanation' => '"Nowhere" + phrase at the start inverts "be" and the subject: "Nowhere in the report is this issue mentioned."',
			),
			array(
				'id'          => 'c2-24',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Little', 'did', 'I', 'imagine', 'that', 'we', 'would', 'meet', 'again.' ),
				'explanation' => '"Little" at the start inverts with "did": "Little did I imagine that we would meet again."',
			),
			array(
				'id'          => 'c2-25',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Had', 'it', 'not', 'been', 'for', 'the', 'rain,', 'the', 'match', 'would', 'have', 'continued.' ),
				'explanation' => '"Had it not been for" replaces "if it hadn’t been for": "Had it not been for the rain, the match would have continued."',
			),
			array(
				'id'          => 'c2-26',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Should', 'you', 'require', 'further', 'information,', 'please', 'contact', 'us.' ),
				'explanation' => '"Should" + subject replaces "if": "Should you require further information, please contact us."',
			),
			array(
				'id'          => 'c2-27',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Only', 'when', 'the', 'evidence', 'emerged', 'did', 'the', 'minister', 'resign.' ),
				'explanation' => '"Only when" + clause at the start inverts the main clause: "Only when the evidence emerged did the minister resign."',
			),
			array(
				'id'          => 'c2-28',
				'topic'       => 'fronting',
				'level'       => 'C2',
				'words'       => array( 'Strange', 'as', 'it', 'may', 'seem,', 'the', 'story', 'is', 'true.' ),
				'explanation' => 'Adjective + "as" + subject + verb makes a concessive clause: "Strange as it may seem, the story is true."',
			),
			array(
				'id'          => 'c2-29',
				'topic'       => 'fronting',
				'level'       => 'C2',
				'words'       => array( 'Try', 'as', 'he', 'might,', 'he', 'could', 'not', 'open', 'the', 'door.' ),
				'explanation' => 'Verb + "as" + subject + "might" means "however hard he tried": "Try as he might, he could not open the door."',
			),
			array(
				'id'          => 'c2-30',
				'topic'       => 'fronting',
				'level'       => 'C2',
				'words'       => array( 'Much', 'as', 'I', 'admire', 'her,', 'I', 'cannot', 'agree', 'with', 'her.' ),
				'explanation' => '"Much as" means "although … very much": "Much as I admire her, I cannot agree with her."',
			),
			array(
				'id'          => 'c2-31',
				'topic'       => 'fronting',
				'level'       => 'C2',
				'words'       => array( 'Gone', 'are', 'the', 'days', 'when', 'people', 'wrote', 'letters.' ),
				'explanation' => 'A fronted participle is followed by "be" + subject: "Gone are the days when people wrote letters."',
			),
			array(
				'id'          => 'c2-32',
				'topic'       => 'fronting',
				'level'       => 'C2',
				'words'       => array( 'Into', 'the', 'room', 'walked', 'a', 'tall', 'stranger.' ),
				'explanation' => 'A fronted place phrase is followed by the verb and then the subject: "Into the room walked a tall stranger."',
			),
			array(
				'id'          => 'c2-33',
				'topic'       => 'subjunctive',
				'level'       => 'C2',
				'words'       => array( 'Were', 'I', 'to', 'accept', 'the', 'offer,', 'I', 'would', 'have', 'to', 'move', 'abroad.' ),
				'explanation' => '"Were" + subject + to-infinitive replaces "if": "Were I to accept the offer, I would have to move abroad."',
			),
			array(
				'id'          => 'c2-34',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'No', 'sooner', 'had', 'the', 'plane', 'landed', 'than', 'the', 'passengers', 'stood', 'up.' ),
				'explanation' => '"No sooner" + "had" + subject + past participle, then "than": "No sooner had the plane landed than the passengers stood up."',
			),
			array(
				'id'          => 'c2-35',
				'topic'       => 'inversion',
				'level'       => 'C2',
				'words'       => array( 'Only', 'in', 'recent', 'years', 'has', 'the', 'problem', 'been', 'recognised.' ),
				'explanation' => '"Only in recent years" at the start inverts "has" and the subject: "Only in recent years has the problem been recognised."',
			),
			array(
				'id'          => 'c2-36',
				'topic'       => 'fronting',
				'level'       => 'C2',
				'words'       => array( 'Hardly', 'a', 'day', 'passes', 'without', 'some', 'new', 'scandal.' ),
				'explanation' => '"Hardly a day" + verb, then "without" + noun phrase: "Hardly a day passes without some new scandal."',
			),
		);
	}

	/** Topic slugs to display labels. */
	private const TOPIC_LABELS = array(
		'word-order'         => 'Word order',
		'adverb-placement'   => 'Adverb placement',
		'question-order'     => 'Question word order',
		'clause-order'       => 'Clause order',
		'inversion'          => 'Inversion',
		'cleft-sentence'     => 'Cleft sentences',
		'relative-clause'    => 'Relative clauses',
		'participle-clause'  => 'Participle clauses',
		'subjunctive'        => 'Subjunctive inversion',
		'fronting'           => 'Fronting',
	);

	public static function topic_label( string $topic ): string {
		return self::TOPIC_LABELS[ $topic ] ?? $topic;
	}

	/**
	 * Items at one level, or every item when the level is empty.
	 *
	 * @return list<array{id:string,topic:string,level:string,words:list<string>,explanation:string}>
	 */
	public static function by_level( string $level ): array {
		if ( '' === $level ) {
			return self::items();
		}

		return array_values(
			array_filter(
				self::items(),
				static fn ( array $item ): bool => $item['level'] === $level
			)
		);
	}

	/**
	 * Levels actually present in the bank, in CEFR order.
	 *
	 * @return list<string>
	 */
	public static function available_levels(): array {
		$present = array();
		foreach ( self::items() as $item ) {
			$present[ $item['level'] ] = true;
		}

		return array_values(
			array_intersect(
				array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' ),
				array_keys( $present )
			)
		);
	}
}
