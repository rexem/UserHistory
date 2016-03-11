<?php
/**
 * SpecialPage for UserHistory extension
 *
 * @ingroup Extensions
 */

class SpecialUserHistory extends SpecialPage {
	public function __construct() {
		parent::__construct( 'UserHistory' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *
	 * @return bool
	 */
	public function execute( $sub ) {
		// display only for logged in users
		if ( !$this->getUser()->isLoggedIn() ) {
			$this->getOutput()->showErrorPage( 'exception-nologin', 'exception-nologin-text' );

			return false;
		}

		$out = $this->getOutput();

		$out->setPageTitle( $this->msg( 'userhistory' ) );
		$out->addWikiMsg( 'userhistory-description' );
		$out->addHTML( $this->buildTable( $this->getContext()->getUser()->getId() ) );

		return true;
	}

	protected function getGroupName() {
		return 'other';
	}

	/**
	 * Generates ready to output results table
	 *
	 * @param $user_id
	 *
	 * @return string
	 */
	protected function buildTable( $user_id ) {
		$out = Xml::openElement( 'table', array( 'id' => 'user-history-table', 'class' => 'wikitable' ) );

		// headers
		$out .= "<tr>
			<th>" . $this->msg( 'userhistory-accessed-date' ) . "</th>
			<th>" . $this->msg( 'userhistory-accessed-page' ) . "</th>
		</tr>";

		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select(
			array( 'user_history', 'page' ),
			array( 'uh_timestamp', 'page_title', 'page_id' ),
			array( 'uh_user_id' => $user_id ),
			__METHOD__,
			array( 'ORDER BY' => 'uh_timestamp DESC' ),
			array( 'page' => array( 'INNER JOIN', array( 'user_history.uh_page_id=page.page_id' )) )
		);

		// build rows
		foreach ( $res as $row ) {
			$elapsedTime = $this->getPrettyElapsedTime( $row->uh_timestamp );
			$linkToPage = Linker::link( Title::newFromID( $row->page_id ) );

			$out .= "<tr>
				<td title=\"" . wfTimestamp( TS_DB, $row->uh_timestamp ) . "\">{$elapsedTime}</td>
				<td>{$linkToPage}</td>
			</tr>\n";
		}

		$out .= Xml::closeElement( 'table' );

		return $out;
	}

	/**
	 * Returns human-friendly string of elapsed time since the provided timestamp
	 *
	 * @param $timestamp
	 *
	 * @return string Human readable date/time string
	 */
	protected function getPrettyElapsedTime( $timestamp ) {
		$elapsed = wfTimestampNow() - $timestamp;

		if ( $elapsed <= 1 ) {
			return $this->msg( 'just-now' );
		} elseif ( $elapsed < 60 ) {
			return $this->msg( 'seconds-ago', $elapsed );
		} elseif ( $elapsed < 60 * 60 ) {
			return $this->msg( 'minutes-ago', round( $elapsed / 60 ) );
		} elseif ( $elapsed < 60 * 60 * 24 ) {
			return $this->msg( 'hours-ago', round( $elapsed / ( 60 * 60 ) ) );
		}

		return wfTimestamp( TS_DB );
	}
}
