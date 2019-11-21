/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { SelectControl, Spinner } from '@wordpress/components';

const defaultEvent = { label: __( 'Select event', 'jetpack' ), value: '' };

class EventbriteEdit extends Component {
	state = {
		fetchingEvents: true,
		events: [],
	};

	componentDidMount() {
		this.getEvents();
	}

	getEvents = async () => {
		const response = await apiFetch( {
			path: '/jetpack/v4/integrations/eventbrite',
		} );

		const events = response.events.map( event => ( {
			label: event.post_title,
			value: event.ID,
		} ) );

		this.setState( { events, fetchingEvents: false } );
	};

	setEvent = eventId => {
		this.props.setAttributes( { eventId: eventId } );
	};

	render() {
		const { events, fetchingEvents } = this.state;

		if ( fetchingEvents ) {
			return (
				<div className="wp-block-jetpack-eventbrite is-loading">
					<Spinner />
					<p>{ __( 'Loading events…', 'jetpack' ) }</p>
				</div>
			);
		}

		return (
			<SelectControl
				label={ __( 'Event', 'jetpack' ) }
				value={ this.props.attributes.eventId }
				options={ [ defaultEvent, ...events ] }
				onChange={ this.setEvent }
			/>
		);
	}
}

export default EventbriteEdit;