<?php
/*
 * Copyright (C) 2015 Michael Herold <quabla@hemio.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace hemio\edentata\module\dns;

use hemio\edentata\gui;
use hemio\form;
use hemio\html;

/**
 * Description of RegisteredDetails
 *
 * @author Michael Herold <quabla@hemio.de>
 */
class RegisteredDetails extends Window
{

    public function content($registered)
    {
        $window = $this->newWindow(_('Domain'), $registered);

        $menu = new gui\HeaderbarMenu();
        $menu->addEntry(
            $this->request->derive('custom_create', true)
            , _('Create custom DNS record')
        );

        $window->addButtonRight($menu);

        $window->addButtonRight(
            new gui\LinkButton(
            $this->request->derive(
                'service_create'
                , $registered
            )
            , _('Add Sub-Domain')
            )
        );

        $window
            ->addChild(new gui\Fieldset(_('Domain Service Activation')))
            ->addChild($this->service($registered));

        $custom = $this->custom($registered);
        if (!($custom instanceof html\Nothing))
            $window
                ->addChild(new gui\Fieldset(_('Custom DNS Entries')))
                ->addChild($custom);

        return $window;
    }

    protected function service($registered)
    {
        $service = $this->db->serviceDomainSelect($registered)->fetchAll();

        $list = new gui\Listbox();
        foreach ($service as $domain) {
            $dom       = $domain['domain'];
            $container = new form\Container;

            $container->addChild(new html\String($dom));

            $servicesActive = $this->db->serviceSelect($dom)->fetchAll();
            if (!empty($servicesActive)) {
                $ul = new html\Ul;
                $container->addChild($ul);

                foreach ($servicesActive as $act) {
                    $li = $ul->addLine();
                    $li->addChild(new html\String($act['service']));
                    $li->addChild(new gui\Progress($act['backend_status']));
                }
            }


            $list->addLinkEntry(
                $this->request->derive(
                    'service_details'
                    , $registered
                    , $dom
                )
                , $container
            );
        }

        return $list;
    }

    protected function custom($registered)
    {
        $service = $this->db->customSelect($registered)->fetchAll();

        if (empty($service))
            return new html\Nothing;

        $list = new gui\Listbox();
        foreach ($service as $record) {
            $domain = $record['domain'];
            $span   = new html\Span;
            $ul     = new html\Ul();

            $rdata = (array) json_decode($record['rdata']);
            ksort($rdata);
            foreach ($rdata as $key => $value) {
                $ul->addLine(new html\String(sprintf('%s = %s', $key, $value)));
            }

            $span->addChild(new html\String($domain.' '.$record['type']));
            $span->addChild($ul);

            $a = $list->addLinkEntry(
                $this->request->derive(
                    'custom_details'
                    , $registered
                    , $record['id']
                )
                , $span
                , $record['backend_status']
            );
            $span->setCssProperty('font-family', 'monospace');
        }

        return $list;
    }
}
