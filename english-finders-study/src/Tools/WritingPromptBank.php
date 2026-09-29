<?php
/**
 * Writing tasks for the AI Writing Feedback tool (1.18.0).
 *
 * Each CEFR level has a handful of tasks of the kind that level's exams and
 * course "Can-Do" tasks set, plus a free-topic option. The word range is the
 * length a learner at that level is usually asked to write; the tool accepts
 * a little either side of it (see WritingFeedback::word_limits()) rather than
 * refusing a learner who wrote 38 words for a 40-word task.
 *
 * The bank is small and static on purpose: the feedback, not the task, is
 * what the AI provides, and a fixed task lets the model judge task fit.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WritingPromptBank {
	public const LEVELS = array( 'A1', 'A2', 'B1', 'B2', 'C1', 'C2' );

	/** Usual length asked for at each level, in words: [min, max]. */
	private const RANGES = array(
		'A1' => array( 25, 60 ),
		'A2' => array( 40, 90 ),
		'B1' => array( 80, 150 ),
		'B2' => array( 120, 220 ),
		'C1' => array( 150, 280 ),
		'C2' => array( 180, 320 ),
	);

	/**
	 * Word range for a level.
	 *
	 * @param string $level CEFR level.
	 * @return array{0:int,1:int}
	 */
	public static function range( string $level ): array {
		return self::RANGES[ $level ] ?? self::RANGES['B1'];
	}

	/**
	 * Every task, keyed by id.
	 *
	 * @return array<string,array{id:string,level:string,title:string,task:string}>
	 */
	public static function all(): array {
		$items = array();
		foreach ( self::rows() as $row ) {
			$items[ $row[0] ] = array(
				'id'    => $row[0],
				'level' => $row[1],
				'title' => $row[2],
				'task'  => $row[3],
			);
		}

		return $items;
	}

	/**
	 * One task, or null.
	 *
	 * @param string $id Task id.
	 * @return array{id:string,level:string,title:string,task:string}|null
	 */
	public static function get( string $id ): ?array {
		return self::all()[ $id ] ?? null;
	}

	/**
	 * Tasks grouped by level, for the browser.
	 *
	 * @return array<string,list<array{id:string,title:string,task:string,min:int,max:int}>>
	 */
	public static function for_browser(): array {
		$out = array();
		foreach ( self::all() as $item ) {
			$range                   = self::range( $item['level'] );
			$out[ $item['level'] ][] = array(
				'id'    => $item['id'],
				'title' => $item['title'],
				'task'  => $item['task'],
				'min'   => $range[0],
				'max'   => $range[1],
			);
		}

		return $out;
	}

	/**
	 * Raw rows: id, level, title, task.
	 *
	 * Ids are stable (they key the learner's saved drafts), so a task is
	 * reworded in place rather than renumbered.
	 *
	 * @return list<array{0:string,1:string,2:string,3:string}>
	 */
	private static function rows(): array {
		return array(
			// A1.
			array( 'a1-me', 'A1', 'Introduce yourself', 'Write about yourself: your name, age, where you are from, where you live, your job or studies, and one thing you like.' ),
			array( 'a1-family', 'A1', 'My family', 'Write about your family. Who is in your family? What are their names and ages? What do they do?' ),
			array( 'a1-day', 'A1', 'My day', 'Write about your usual day. What time do you get up? What do you do in the morning, afternoon and evening?' ),
			array( 'a1-home', 'A1', 'My home', 'Describe your home. Where is it? How many rooms are there? What is your favourite room, and why?' ),
			array( 'a1-postcard', 'A1', 'A postcard', 'You are on holiday. Write a postcard to a friend. Say where you are, what the weather is like and what you can see.' ),
			array( 'a1-free', 'A1', 'My own topic', 'Write about any topic you like, using simple sentences.' ),
			// A2.
			array( 'a2-weekend', 'A2', 'Last weekend', 'Write about what you did last weekend. Where did you go, who were you with, and did you enjoy it?' ),
			array( 'a2-email-invite', 'A2', 'An invitation', 'Write an email to a friend inviting them to your birthday party. Say when and where it is, and what you will do.' ),
			array( 'a2-town', 'A2', 'My town', 'Describe your town or city. What can people do there? What do you like and not like about it?' ),
			array( 'a2-plans', 'A2', 'Next summer', 'Write about your plans for next summer. Where are you going to go, and what are you going to do?' ),
			array( 'a2-person', 'A2', 'A person I admire', 'Write about a person you admire. Who are they, what are they like, and why do you admire them?' ),
			array( 'a2-free', 'A2', 'My own topic', 'Write about any topic you like. Try to use the past simple and at least one future form.' ),
			// B1.
			array( 'b1-experience', 'B1', 'A memorable experience', 'Write about a memorable experience you have had. Describe what happened and explain why you remember it.' ),
			array( 'b1-email-complaint', 'B1', 'A complaint email', 'You bought something online and it arrived broken. Write an email to the shop explaining the problem and saying what you want them to do.' ),
			array( 'b1-opinion-phones', 'B1', 'Phones at school', 'Should students be allowed to use mobile phones at school? Give your opinion with reasons and examples.' ),
			array( 'b1-story', 'B1', 'A short story', 'Write a short story that begins: "When I opened the door, I couldn\'t believe my eyes."' ),
			array( 'b1-review', 'B1', 'A review', 'Write a review of a film, book or restaurant you know. Describe it and say whether you would recommend it.' ),
			array( 'b1-free', 'B1', 'My own topic', 'Write about any topic you like. Link your ideas with words like because, although and however.' ),
			// B2.
			array( 'b2-essay-cities', 'B2', 'City or countryside', 'Essay: Is it better to grow up in a city or in the countryside? Discuss both sides and give your own opinion.' ),
			array( 'b2-report', 'B2', 'A report', 'Your school or company wants to improve its facilities. Write a short report describing the current situation and making two recommendations.' ),
			array( 'b2-letter-apply', 'B2', 'A job application', 'Write a letter applying for a summer job at an international holiday camp. Describe your skills and experience.' ),
			array( 'b2-article-tech', 'B2', 'An article', 'Write an article for a student magazine: "How technology has changed the way we learn."' ),
			array( 'b2-review', 'B2', 'A review', 'Write a review of a place you have visited for a travel website. Include what visitors should and should not expect.' ),
			array( 'b2-free', 'B2', 'My own topic', 'Write about any topic you like. Use a clear structure with an introduction, main points and a conclusion.' ),
			// C1.
			array( 'c1-essay-work', 'C1', 'Remote work', 'Essay: Working from home benefits employees more than employers. To what extent do you agree?' ),
			array( 'c1-proposal', 'C1', 'A proposal', 'Write a proposal to your local council suggesting how to make your area greener. Justify your ideas and address likely objections.' ),
			array( 'c1-review', 'C1', 'A critical review', 'Write a critical review of a book, series or exhibition, evaluating its strengths and weaknesses for a well-read audience.' ),
			array( 'c1-letter-editor', 'C1', 'A letter to the editor', 'A newspaper argued that tourism does more harm than good. Write a letter to the editor responding to this view.' ),
			array( 'c1-free', 'C1', 'My own topic', 'Write about any topic you like, aiming for a precise, well-organised argument.' ),
			// C2.
			array( 'c2-essay-ai', 'C2', 'Machines and creativity', 'Essay: Can a machine be creative? Develop a nuanced argument, considering the strongest counter-arguments.' ),
			array( 'c2-article-language', 'C2', 'Language and identity', 'Write an article exploring how the languages we speak shape our sense of identity.' ),
			array( 'c2-review-compare', 'C2', 'A comparative review', 'Compare two works (books, films or albums) that treat a similar theme, and evaluate which does so more effectively.' ),
			array( 'c2-speech', 'C2', 'A persuasive speech', 'Write the text of a short speech persuading an audience of young adults to take part in local democracy.' ),
			array( 'c2-free', 'C2', 'My own topic', 'Write about any topic you like, in a style suited to an educated reader.' ),
		);
	}
}
