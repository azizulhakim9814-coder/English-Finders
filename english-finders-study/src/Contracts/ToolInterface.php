<?php
/**
 * Tool contract.
 *
 * Every practice tool implements this. Deliberately small: the more a contract
 * demands, the more each tool has to reimplement, and the harder it is to keep
 * twelve of them consistent.
 *
 * @package EnglishFindersStudy
 */

declare(strict_types=1);

namespace EnglishFindersStudy\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface ToolInterface {
	/** Stable identifier, e.g. "vocabulary-quiz". Used in URLs and settings. */
	public function id(): string;

	/** Human-readable name, e.g. "Vocabulary Quiz". Shown in the admin tool list. */
	public function title(): string;

	/** Shortcode tag without brackets, e.g. "efs_vocabulary_quiz". Shown in the admin tool list. */
	public function shortcode_tag(): string;

	/**
	 * Skill category.
	 *
	 * One of ToolCatalog::CATEGORIES. Exactly one — a tool that seems to belong
	 * to two categories is usually two tools, or is categorised by how it works
	 * rather than what it teaches.
	 */
	public function category(): string;

	/**
	 * Cross-cutting attributes, e.g. "teacher", "ai".
	 *
	 * Attributes are filters, not categories: a grammar worksheet for teachers
	 * is a grammar tool tagged for teachers, so it appears in grammar
	 * navigation and in a teacher-filtered view without being duplicated.
	 *
	 * @return list<string>
	 */
	public function attributes(): array;

	/** Register hooks. Must not query or render — this runs on every request. */
	public function register(): void;

	/**
	 * Render the tool.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 */
	public function render( array $atts = array() ): string;
}
