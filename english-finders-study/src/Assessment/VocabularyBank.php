<?php
/**
 * Vocabulary items for the English Level Test (Phase A4).
 *
 * Why a curated bank rather than the dictionary: the dictionary's stored
 * definition is WordNet sense 0, and OEWN orders senses by synset id, not by
 * how common they are -- so for roughly 1 in 6 list-verified words it is a
 * rare meaning (checked on the live site 2026-09-23: "dot" -> "street name
 * for lysergic acid diethylamide", "award" -> "a grant made by a law
 * court", "contractor" -> "a bodily organ that contracts"). A learner who
 * knows the word would still miss the question, which in a placement test
 * lowers their level for the wrong reason. Nothing in the data marks the
 * common sense (only 154 of 115,302 senses carry their own CEFR level), so
 * this can't be fixed with a query.
 *
 * Levels are not guessed here: every word was taken from the live
 * dictionary's list-verified CEFR tags (`cefr_source` = `verified`, or
 * `octanove` where marked) at exactly the level it is filed under. Only the
 * definitions are hand-written -- one short, learner-level description of
 * the word's everyday meaning, which never contains the word itself.
 *
 * 1.15.0 grew the bank from 14 to 30 words per level, so a retaken test
 * mostly shows new words. All 96 new words were checked against the live
 * dictionary on 2026-09-27 and are `verified` at exactly their level (none
 * `octanove`). None is a synonym of another word at its level, because
 * that word's definition could appear as a distractor and would then also
 * fit.
 *
 * The distractors for a question are other words' definitions from the
 * same level, so every option is a real, correct definition of something;
 * only one fits the word asked.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Assessment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VocabularyBank {
	/**
	 * @return list<array{id:string,level:string,word:string,pos:string,definition:string}>
	 */
	public static function items(): array {
		$raw = array(
			'A1' => array(
				array( 'party', 'noun', 'a social event where people meet to eat, drink and have fun' ),
				array( 'expensive', 'adjective', 'costing a lot of money' ),
				array( 'delicious', 'adjective', 'tasting very good' ),
				array( 'sister', 'noun', 'a girl or woman who has the same parents as you' ),
				array( 'skirt', 'noun', 'a piece of women\'s clothing that hangs down from the waist' ),
				array( 'guitar', 'noun', 'a musical instrument with strings that you play with your fingers' ),
				array( 'airplane', 'noun', 'a vehicle with wings that flies through the air' ),
				array( 'chair', 'noun', 'a seat for one person, with a back and usually four legs' ),
				array( 'teach', 'verb', 'to help someone learn something' ),
				array( 'listen', 'verb', 'to pay attention to a sound or to what someone is saying' ),
				array( 'forget', 'verb', 'to not remember something' ),
				array( 'celebrate', 'verb', 'to do something enjoyable because of a special day or event' ),
				array( 'holiday', 'noun', 'a time when you do not have to go to work or school' ),
				array( 'building', 'noun', 'a structure with walls and a roof, such as a house or an office' ),
				array( 'kitchen', 'noun', 'the room where you prepare and cook food' ),
				array( 'breakfast', 'noun', 'the first meal of the day, eaten in the morning' ),
				array( 'hungry', 'adjective', 'wanting or needing to eat' ),
				array( 'tired', 'adjective', 'needing to rest or sleep' ),
				array( 'window', 'noun', 'an opening in a wall, with glass in it, that lets in light and air' ),
				array( 'river', 'noun', 'a long line of water that flows across the land to the sea' ),
				array( 'library', 'noun', 'a place where you can borrow books to read' ),
				array( 'beach', 'noun', 'an area of sand or small stones next to the sea' ),
				array( 'bottle', 'noun', 'a glass or plastic container with a narrow top, for drinks and other liquids' ),
				array( 'uncle', 'noun', 'the brother of your mother or father' ),
				array( 'ticket', 'noun', 'a small piece of paper that shows you have paid to travel or to go into a place' ),
				array( 'hospital', 'noun', 'a place where sick or injured people are looked after by doctors and nurses' ),
				array( 'arrive', 'verb', 'to reach a place at the end of a journey' ),
				array( 'carry', 'verb', 'to hold something and take it with you from one place to another' ),
				array( 'quickly', 'adverb', 'fast, or in a short time' ),
				array( 'angry', 'adjective', 'feeling very annoyed, for example because someone has behaved badly' ),
			),
			'A2' => array(
				array( 'scissors', 'noun', 'a tool with two blades that you use for cutting paper or cloth' ),
				array( 'balcony', 'noun', 'a small area outside an upstairs window where you can stand or sit' ),
				array( 'storm', 'noun', 'very bad weather with strong wind and heavy rain' ),
				array( 'avoid', 'verb', 'to stay away from someone or something' ),
				array( 'discover', 'verb', 'to find or learn something for the first time' ),
				array( 'bored', 'adjective', 'tired and unhappy because nothing interesting is happening' ),
				array( 'frightened', 'adjective', 'feeling afraid' ),
				array( 'mood', 'noun', 'the way you feel at a particular time, for example happy or angry' ),
				array( 'tidy', 'adjective', 'neat, with everything in its proper place' ),
				array( 'complaint', 'noun', 'something you say or write to show that you are not happy about something' ),
				array( 'exhibition', 'noun', 'a public show of paintings, photos or other interesting objects' ),
				array( 'repair', 'noun', 'work that is done to fix something that is broken' ),
				array( 'nowadays', 'adverb', 'at the present time, compared with the past' ),
				array( 'scenery', 'noun', 'the natural features of an area, such as hills and forests, especially when they are beautiful' ),
				array( 'lend', 'verb', 'to give something to someone for a short time, expecting them to give it back' ),
				array( 'receipt', 'noun', 'a piece of paper that shows you have paid for something' ),
				array( 'crowded', 'adjective', 'full of people' ),
				array( 'polite', 'adjective', 'behaving in a way that shows respect for other people, for example by saying "please" and "thank you"' ),
				array( 'fridge', 'noun', 'a cold cupboard in the kitchen where you keep food fresh' ),
				array( 'wallet', 'noun', 'a small flat case for keeping money and cards in, usually carried in a pocket' ),
				array( 'journey', 'noun', 'the act of travelling from one place to another, especially a long way' ),
				array( 'passenger', 'noun', 'a person who is travelling in a car, bus, train or plane but is not driving it' ),
				array( 'accident', 'noun', 'something bad that happens by chance, such as a crash, often hurting someone' ),
				array( 'invite', 'verb', 'to ask someone to come to a party, a meal or another event' ),
				array( 'prefer', 'verb', 'to like one thing more than another' ),
				array( 'explain', 'verb', 'to make something clear and easy to understand by giving details' ),
				array( 'earn', 'verb', 'to get money for the work that you do' ),
				array( 'knock', 'verb', 'to hit a door with your hand so that the people inside know you are there' ),
				array( 'probably', 'adverb', 'almost certainly; very likely' ),
				array( 'brave', 'adjective', 'not afraid of doing dangerous or difficult things' ),
			),
			'B1' => array(
				array( 'jungle', 'noun', 'a thick tropical forest with many trees and plants growing close together' ),
				array( 'dolphin', 'noun', 'an intelligent sea animal that looks like a large fish and breathes air' ),
				array( 'hostel', 'noun', 'a cheap place to stay, often with shared rooms, used especially by young travellers' ),
				array( 'basement', 'noun', 'a room or floor of a building that is below ground level' ),
				array( 'honeymoon', 'noun', 'a holiday that two people take just after they get married' ),
				array( 'butcher', 'noun', 'a person whose job is to cut and sell meat' ),
				array( 'delighted', 'adjective', 'very pleased and happy' ),
				array( 'humid', 'adjective', '(of air or weather) warm and wet in a way that feels uncomfortable' ),
				array( 'extinct', 'adjective', 'no longer existing, because every animal or plant of that kind has died' ),
				array( 'ignore', 'verb', 'to pay no attention to someone or something' ),
				array( 'defend', 'verb', 'to protect someone or something from attack' ),
				array( 'adapt', 'verb', 'to change your behaviour so that it suits a new situation' ),
				array( 'briefly', 'adverb', 'for a short time' ),
				array( 'embarrass', 'verb', 'to make someone feel shy, silly or uncomfortable in front of other people' ),
				array( 'luggage', 'noun', 'the bags and cases that you take with you when you travel' ),
				array( 'nephew', 'noun', 'the son of your brother or sister' ),
				array( 'cough', 'verb', 'to push air out of your throat with a sudden loud noise, often because you are ill' ),
				array( 'cancel', 'verb', 'to decide that a planned event will not happen' ),
				array( 'deadline', 'noun', 'the latest time or date by which something must be finished' ),
				array( 'generous', 'adjective', 'happy to give money, time or help to other people' ),
				array( 'curious', 'adjective', 'wanting to know or learn about something' ),
				array( 'exhausted', 'adjective', 'extremely tired' ),
				array( 'refund', 'noun', 'money that is given back to you, for example when you return something to a shop' ),
				array( 'volunteer', 'noun', 'a person who does work without being paid, because they want to help' ),
				array( 'ambulance', 'noun', 'a vehicle that takes injured or sick people to hospital' ),
				array( 'whisper', 'verb', 'to speak very quietly, using your breath rather than your voice' ),
				array( 'persuade', 'verb', 'to make someone agree to do something by giving them good reasons' ),
				array( 'eventually', 'adverb', 'in the end, after a long time or a lot of problems' ),
				array( 'tent', 'noun', 'a shelter made of cloth and held up by poles, which you sleep in when camping' ),
				array( 'stubborn', 'adjective', 'refusing to change your opinion or behaviour, even when you should' ),
			),
			'B2' => array(
				array( 'venue', 'noun', 'the place where an event such as a concert or a match happens' ),
				array( 'lecturer', 'noun', 'someone who teaches at a university' ),
				array( 'physician', 'noun', 'a doctor, especially one who treats illness with medicine rather than surgery' ),
				array( 'infant', 'noun', 'a baby or a very young child' ),
				array( 'laundry', 'noun', 'clothes and sheets that need to be washed or have just been washed' ),
				array( 'fraud', 'noun', 'the crime of deceiving people in order to get money' ),
				array( 'hesitation', 'noun', 'a pause before you do or say something, because you are not sure' ),
				array( 'dense', 'adjective', 'containing a lot of things or people very close together' ),
				array( 'hollow', 'adjective', 'having an empty space inside' ),
				array( 'bustling', 'adjective', 'full of busy, noisy activity' ),
				array( 'legitimate', 'adjective', 'allowed by the law, or fair and reasonable' ),
				array( 'dominate', 'verb', 'to control or have power over someone or something' ),
				array( 'exclaim', 'verb', 'to say something suddenly and loudly, because of a strong feeling' ),
				array( 'deliberately', 'adverb', 'on purpose, not by accident' ),
				array( 'reluctant', 'adjective', 'not wanting to do something, and so slow to do it' ),
				array( 'compassion', 'noun', 'a strong feeling of sympathy for people who are suffering, and a wish to help them' ),
				array( 'dilemma', 'noun', 'a situation in which you have to make a difficult choice between two options' ),
				array( 'consensus', 'noun', 'general agreement among a group of people' ),
				array( 'outbreak', 'noun', 'the sudden start of something unpleasant, such as a disease or a war' ),
				array( 'postpone', 'verb', 'to change an event to a later time or date' ),
				array( 'clarify', 'verb', 'to make something easier to understand by explaining it in more detail' ),
				array( 'exaggerate', 'verb', 'to describe something as bigger, better or worse than it really is' ),
				array( 'thoroughly', 'adverb', 'completely and carefully, paying attention to every part' ),
				array( 'drought', 'noun', 'a long period of time with little or no rain' ),
				array( 'flee', 'verb', 'to escape from a place or a danger by leaving quickly' ),
				array( 'ambiguous', 'adjective', 'having more than one possible meaning, so that it is not clear' ),
				array( 'commute', 'verb', 'to travel regularly between your home and your place of work' ),
				array( 'mortgage', 'noun', 'a loan from a bank that you use to buy a house' ),
				array( 'refugee', 'noun', 'a person who has had to leave their country because of war or danger' ),
				array( 'plumber', 'noun', 'a person whose job is to fit and repair water pipes, toilets and baths' ),
			),
			'C1' => array(
				array( 'verdict', 'noun', 'the official decision made by a judge or jury at the end of a court case' ),
				array( 'rapport', 'noun', 'a friendly relationship in which people understand each other well' ),
				array( 'sentiment', 'noun', 'an opinion or feeling that you have about something' ),
				array( 'prolific', 'adjective', 'producing a large amount of work or results' ),
				array( 'avid', 'adjective', 'very keen and enthusiastic about something' ),
				array( 'reminiscent', 'adjective', 'making you think of something similar from the past' ),
				array( 'disdain', 'noun', 'the feeling that someone or something is not good enough to deserve your respect' ),
				array( 'altruism', 'noun', 'caring about other people and helping them without wanting anything in return' ),
				array( 'exuberant', 'adjective', 'full of energy, excitement and happiness' ),
				array( 'oversee', 'verb', 'to watch and manage work to make sure that it is done correctly' ),
				array( 'misplace', 'verb', 'to put something somewhere and then be unable to find it' ),
				array( 'interject', 'verb', 'to suddenly add a remark while someone else is speaking' ),
				array( 'cynicism', 'noun', 'the belief that people usually act only in their own interests' ),
				array( 'flicker', 'noun', 'a light that shines unsteadily, quickly getting brighter and weaker' ),
				array( 'haggle', 'verb', 'to argue with a seller about the price of something' ),
				array( 'itinerary', 'noun', 'a plan of a journey, listing the places you will visit and when' ),
				array( 'gruelling', 'adjective', 'extremely tiring and difficult, and lasting a long time' ),
				array( 'nocturnal', 'adjective', '(of animals) active at night rather than during the day' ),
				array( 'remorse', 'noun', 'a strong feeling of being sorry for something wrong that you have done' ),
				array( 'sarcasm', 'noun', 'saying the opposite of what you mean, in order to mock someone or to be funny' ),
				array( 'hypocrisy', 'noun', 'claiming to have beliefs or standards that you do not actually follow yourself' ),
				array( 'resilient', 'adjective', 'able to recover quickly from difficulties or problems' ),
				array( 'apprehensive', 'adjective', 'slightly worried or nervous about something that is going to happen' ),
				array( 'candid', 'adjective', 'honest and direct, even when the truth may be unwelcome' ),
				array( 'clutter', 'noun', 'a lot of objects lying around in an untidy way' ),
				array( 'irate', 'adjective', 'extremely angry' ),
				array( 'lethargic', 'adjective', 'having little energy and not wanting to do anything' ),
				array( 'ludicrous', 'adjective', 'so silly or unreasonable that it is laughable' ),
				array( 'squander', 'verb', 'to waste money, time or opportunities in a foolish way' ),
				array( 'tranquil', 'adjective', 'calm, quiet and peaceful' ),
			),
			'C2' => array(
				array( 'austerity', 'noun', 'a situation in which a government sharply cuts its spending in order to reduce debt' ),
				array( 'parochial', 'adjective', 'interested only in local matters and not in wider issues' ),
				array( 'abhorrent', 'adjective', 'morally wrong in a way that you find disgusting' ),
				array( 'drudgery', 'noun', 'hard, boring work that has to be done' ),
				array( 'calamity', 'noun', 'a sudden event that causes great damage or suffering' ),
				array( 'jargon', 'noun', 'special words used by a particular profession or group, which other people find hard to understand' ),
				array( 'colloquial', 'adjective', '(of language) informal, and used in everyday conversation rather than in writing' ),
				array( 'connotation', 'noun', 'an idea or feeling that a word suggests in addition to its basic meaning' ),
				array( 'posterity', 'noun', 'all the people who will live in the future' ),
				array( 'rebuke', 'noun', 'sharp criticism of someone for something they have done' ),
				array( 'lament', 'noun', 'an expression of deep sadness, especially about a death or a loss' ),
				array( 'tetchy', 'adjective', 'easily annoyed or irritated' ),
				array( 'ostensible', 'adjective', 'appearing or stated to be true, although this may not really be so' ),
				array( 'notorious', 'adjective', 'famous for something bad' ),
				array( 'cogent', 'adjective', 'clear, logical and convincing' ),
				array( 'conjecture', 'noun', 'an opinion or idea formed without enough information to prove it' ),
				array( 'derelict', 'adjective', '(of a building or land) empty, not used, and in very bad condition' ),
				array( 'ephemeral', 'adjective', 'lasting for only a very short time' ),
				array( 'euphemism', 'noun', 'a mild or indirect word used instead of one that might seem rude or upsetting' ),
				array( 'haughty', 'adjective', 'behaving in a proud, unfriendly way, as if you are better than other people' ),
				array( 'idiosyncrasy', 'noun', 'an unusual habit or way of behaving that is typical of one particular person' ),
				array( 'impeccable', 'adjective', 'perfect, with no faults at all' ),
				array( 'inscrutable', 'adjective', 'impossible to understand or interpret, especially a person\'s expression' ),
				array( 'meticulous', 'adjective', 'very careful, paying close attention to every detail' ),
				array( 'nonchalant', 'adjective', 'calm and relaxed, as if you do not care or are not worried' ),
				array( 'opulent', 'adjective', 'expensive and luxurious in a showy way' ),
				array( 'precocious', 'adjective', '(of a child) showing adult abilities or behaviour at an unusually early age' ),
				array( 'recluse', 'noun', 'a person who lives alone and avoids other people' ),
				array( 'myriad', 'noun', 'a very large number of something' ),
				array( 'surreptitious', 'adjective', 'done secretly, so that other people do not notice' ),
			),
		);

		$items = array();
		foreach ( $raw as $level => $entries ) {
			foreach ( $entries as $entry ) {
				$items[] = array(
					'id'         => 'voc-' . strtolower( $level ) . '-' . $entry[0],
					'level'      => $level,
					'word'       => $entry[0],
					'pos'        => $entry[1],
					'definition' => $entry[2],
				);
			}
		}

		return $items;
	}

	/**
	 * @return list<array{id:string,level:string,word:string,pos:string,definition:string}>
	 */
	public static function by_level( string $level ): array {
		return array_values(
			array_filter(
				self::items(),
				static fn ( array $item ): bool => $item['level'] === $level
			)
		);
	}
}
