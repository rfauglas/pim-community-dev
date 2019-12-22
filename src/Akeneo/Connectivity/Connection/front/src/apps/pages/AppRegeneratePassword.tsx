import React, {useContext} from 'react';
import {useHistory, useParams} from 'react-router';
import styled from 'styled-components';
import {GreyButton, ImportantButton, Modal} from '../../common';
import {PropsWithTheme} from '../../common/theme';
import {fetchResult} from '../../shared/fetch-result';
import {isErr} from '../../shared/fetch-result/result';
import {NotificationLevel, useNotify} from '../../shared/notify';
import {useRoute} from '../../shared/router';
import {Translate, TranslateContext} from '../../shared/translate';
import {useAppsState} from '../app-state-context';
import {appPasswordRegenerated} from '../actions/apps-actions';

type ResultData = {
    password: string;
};

export const AppRegeneratePassword = () => {
    const history = useHistory();
    const notify = useNotify();
    const translate = useContext(TranslateContext);

    const {code} = useParams<{code: string}>();
    const url = useRoute('akeneo_apps_regenerate_password_rest', {code});

    const [, dispatch] = useAppsState();

    const handleRedirect = () => {
        history.push(`/apps/${code}/edit`);
    };

    const handleClick = async () => {
        const result = await fetchResult<ResultData, unknown>(url, {
            method: 'POST',
        });

        if (isErr(result)) {
            notify(
                NotificationLevel.ERROR,
                translate('akeneo_connectivity.connection.regenerate_password.flash.error')
            );
        } else {
            dispatch(appPasswordRegenerated(code, result.value.password));
            notify(
                NotificationLevel.SUCCESS,
                translate('akeneo_connectivity.connection.regenerate_password.flash.success')
            );
        }

        handleRedirect();
    };

    const description = (
        <>
            <Translate id='akeneo_connectivity.connection.regenerate_password.description' />
            &nbsp;
            <Link href={translate('akeneo_connectivity.connection.regenerate_password.link_url')} target='_blank'>
                <Translate id='akeneo_connectivity.connection.regenerate_password.link' />
            </Link>
        </>
    );

    return (
        <Modal
            subTitle={<Translate id='akeneo_connectivity.connection.connections' />}
            title={<Translate id='akeneo_connectivity.connection.regenerate_password.title' />}
            description={description}
            onCancel={handleRedirect}
        >
            <div className='AknButtonList'>
                <GreyButton onClick={handleRedirect} classNames={['AknButtonList-item']}>
                    <Translate id='pim_common.cancel' />
                </GreyButton>
                <ImportantButton onClick={handleClick} classNames={['AknButtonList-item']}>
                    <Translate id='akeneo_connectivity.connection.regenerate_password.action.regenerate' />
                </ImportantButton>
            </div>
        </Modal>
    );
};

const Link = styled.a`
    color: ${({theme}: PropsWithTheme) => theme.color.blue100};
    text-decoration: underline;
`;
