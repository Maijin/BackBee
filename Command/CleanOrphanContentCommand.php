<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BackBee\AutoLoader\Exception\ClassNotFoundException;
use BackBee\Console\AbstractCommand;

/**
 * This command allows us to clean every orphans contents and its subcontents.
 *
 * @category    BackBee
 *
 * @copyright   Lp digital system
 * @author      e.chau <eric.chau@lp-digital.fr>
 */
class CleanOrphanContentCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('content:clean_orphan')
            ->setDescription('Remove orphans contents and its sub contents')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('bbapp')->getEntityManager();

        $orphans = $em->getConnection()->executeQuery(
            'SELECT c.uid, c.classname FROM content c
             LEFT JOIN content_has_subcontent sc ON sc.content_uid = c.uid
             LEFT JOIN page p ON p.contentset = c.uid
             LEFT JOIN media m ON m.content_uid = c.uid
             WHERE sc.content_uid IS NULL AND p.contentset IS NULL AND m.content_uid IS NULL'
        )->fetchAll();

        $contents_count = $em->getConnection()->executeQuery('SELECT count(*) FROM content')->fetch(\PDO::FETCH_NUM);
        $before_contents_count = $contents_count[0];
        $output->writeln(
            "\nBefore cleaning, content table contains $before_contents_count row(s)"
            ." (including ".count($orphans)." potentials orphans).\n"
        );

        foreach ($orphans as $orphan) {
            try {
                $orphan_object = $em->find($orphan['classname'], $orphan['uid']);
                $em->getRepository($orphan['classname'])->deleteContent($orphan_object);
            } catch (ClassNotFoundException $e) {
                $uid = $orphan['uid'];
                $em->getConnection()->executeQuery(
                    "DELETE FROM content_has_subcontent WHERE content_uid = '$uid' OR parent_uid = '$uid'"
                )->execute();
                $em->getConnection()->executeQuery("DELETE FROM revision WHERE content_uid = '$uid'")->execute();
                $em->getConnection()->executeQuery("DELETE FROM content WHERE uid = '$uid'")->execute();
            }
        }

        $em->flush();

        $contents_count = $em->getConnection()->executeQuery('SELECT count(*) FROM content')->fetch(\PDO::FETCH_NUM);
        $rows_saved = $before_contents_count - $contents_count[0];
        $output->writeln("After cleaning, content table contains $contents_count[0] row(s) ($rows_saved row(s) saved).\n");
    }
}
