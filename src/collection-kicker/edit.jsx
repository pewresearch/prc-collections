/**
 * WordPress Dependencies
 */
import { useBlockProps, Warning } from '@wordpress/block-editor';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal Dependencies
 */
import Icon from './icon';

export default function Edit({}) {
	const blockProps = useBlockProps();

	return (
		<div {...blockProps}>
			<Warning>
				<Flex>
					<FlexItem>
						<Icon />
					</FlexItem>
					<FlexBlock>
						<span>Collection Kicker Template</span>
					</FlexBlock>
				</Flex>
			</Warning>
		</div>
	);
}
