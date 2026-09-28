<?php
/**
 * Read and write access to the entitlements table.
 *
 * The webhook controller is the only writer this repository should have in
 * Phase A0 -- it turns a Paddle event into one of the calls below. Nothing
 * else should be inserting or updating rows in `entitlements` directly.
 *
 * @package EnglishFindersCore
 */

declare(strict_types=1);

namespace EnglishFindersCore\Billing;

use EnglishFindersCore\Database\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class EntitlementRepository {
	/**
	 * Resolve what a WordPress user is entitled to.
	 *
	 * Returns the free entitlement for any user with no row -- callers never
	 * need to special-case "not a subscriber".
	 */
	public function for_user( int $user_id ): Entitlement {
		global $wpdb;

		$table = Schema::table( 'entitlements' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT plan_code, status, current_period_end, cancel_at_period_end, seats FROM {$table} WHERE user_id = %d",
				$user_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? Entitlement::from_row( $row ) : Entitlement::free();
	}

	/**
	 * Create or update the entitlement row for a Paddle subscription.
	 *
	 * Matched on `paddle_subscription_id`, not `user_id`: the row may not have
	 * a user yet (see find_unclaimed_by_customer() below), and this is the one
	 * identifier every subscription.created/updated payload always carries.
	 *
	 * @param array<string,mixed> $data Shaped by PaddleWebhookController::map_subscription().
	 */
	public function upsert_from_subscription( array $data ): void {
		global $wpdb;

		$subscription_id = (string) ( $data['paddle_subscription_id'] ?? '' );
		if ( '' === $subscription_id ) {
			return;
		}

		$table = Schema::table( 'entitlements' );
		$now   = current_time( 'mysql', true );

		$existing_id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM {$table} WHERE paddle_subscription_id = %s",
				$subscription_id
			)
		);

		$fields = array(
			'paddle_customer_id'     => (string) ( $data['paddle_customer_id'] ?? '' ),
			'paddle_subscription_id' => $subscription_id,
			'plan_code'              => (string) ( $data['plan_code'] ?? 'free' ),
			'status'                 => (string) ( $data['status'] ?? 'active' ),
			'current_period_end'     => $data['current_period_end'] ?? null,
			'cancel_at_period_end'   => ! empty( $data['cancel_at_period_end'] ) ? 1 : 0,
			'seats'                  => $data['seats'] ?? null,
			'updated_at'             => $now,
		);

		/*
		 * user_id is set on create only. An update event must never blank out
		 * a user_id that a later claim() call already attached -- Paddle's own
		 * payload does not know about our WordPress user, so it has nothing
		 * useful to say about this column on an update.
		 */
		if ( null === $existing_id && isset( $data['user_id'] ) ) {
			$fields['user_id'] = (int) $data['user_id'];
		}

		if ( null !== $existing_id ) {
			$wpdb->update( $table, $fields, array( 'id' => (int) $existing_id ) );
			return;
		}

		$fields['created_at'] = $now;
		if ( ! array_key_exists( 'user_id', $fields ) ) {
			$fields['user_id'] = null;
		}

		$wpdb->insert( $table, $fields );
	}

	/**
	 * The Paddle customer id behind a user's entitlement row, if any.
	 *
	 * Deliberately not part of Entitlement -- that value object is
	 * documented as billing-provider-agnostic ("independent of how billing
	 * arrived at it"), and a Paddle-specific identifier does not belong on
	 * it. This exists only for the narrow cases that genuinely need to talk
	 * to Paddle directly, such as PaddlePortalClient::create_session()
	 * (Phase A5).
	 */
	public function paddle_customer_id_for_user( int $user_id ): string {
		global $wpdb;

		$table = Schema::table( 'entitlements' );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is prefix-derived, not user input.
				"SELECT paddle_customer_id FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);

		return null !== $id ? (string) $id : '';
	}

	/** Update just the status of an existing subscription row (cancel/past_due/paused events). */
	public function mark_status( string $paddle_subscription_id, string $status ): void {
		if ( '' === $paddle_subscription_id ) {
			return;
		}

		global $wpdb;
		$table = Schema::table( 'entitlements' );

		$wpdb->update(
			$table,
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'paddle_subscription_id' => $paddle_subscription_id )
		);
	}

	/**
	 * Find an unclaimed row (subscribed before an account existed) for a
	 * given Paddle customer, so it can be attached on the user's next login.
	 *
	 * Returns the entitlement row id, or null if there is nothing to claim.
	 */
	public function find_unclaimed_by_customer( string $paddle_customer_id ): ?int {
		if ( '' === $paddle_customer_id ) {
			return null;
		}

		global $wpdb;
		$table = Schema::table( 'entitlements' );

		$id = $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT id FROM {$table} WHERE paddle_customer_id = %s AND user_id IS NULL",
				$paddle_customer_id
			)
		);

		return null !== $id ? (int) $id : null;
	}

	/**
	 * Attach an unclaimed row to a WordPress user.
	 *
	 * Guarded by the row still being unclaimed at write time (not just at
	 * find_unclaimed_by_customer() time) and by the `user_id` unique key,
	 * which would otherwise let a second claim overwrite an existing
	 * subscriber's row. Returns whether the claim actually happened.
	 *
	 * Uses a raw prepared query rather than $wpdb->update(): that helper has
	 * no way to express `WHERE user_id IS NULL` in its where-array, since it
	 * always emits `= %s`/`= %d`, and `user_id = NULL` never matches in SQL.
	 */
	public function claim( int $entitlement_id, int $user_id ): bool {
		global $wpdb;
		$table = Schema::table( 'entitlements' );

		$updated = $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table} SET user_id = %d, updated_at = %s WHERE id = %d AND user_id IS NULL",
				$user_id,
				current_time( 'mysql', true ),
				$entitlement_id
			)
		);

		return false !== $updated && $updated > 0;
	}
}
