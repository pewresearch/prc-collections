/**
 * WordPress Dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';

/**
 * Internal Dependencies
 */
import Icon from './icon';

export default function Edit({}) {
	const blockProps = useBlockProps();

	return (
		<div {...blockProps}>
			<Flex>
				<FlexItem>
					<Icon />
				</FlexItem>
				<FlexBlock>
					<span>
						<strong>Collection Kicker Template</strong>
					</span>
				</FlexBlock>
			</Flex>
		</div>
	);
}
