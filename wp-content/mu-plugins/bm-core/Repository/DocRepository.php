<?php
namespace BM\Core\Repository;

use BM\Core\Database\QueryBuilder;
use BM\Core\Database\Connection;
use BM\Core\Database\Cache;
use BM\Taxonomies\EntityRelations;
use BM\Core\Repository\RepositoryInterface;

class DocRepository implements RepositoryInterface
{

    protected function getTableName(): string
    {
        return 'docs';
    }

    /**
     * Получить документ по типу
     */
    public function findByType($type)
    {
        $cache_key = ['doc', 'type', $type];
        $doc = $this->cache->get($cache_key);

        if (!$doc) {
            $doc = $this->qb->table('docs')
                ->where('document_type', $type)
                ->where('is_current', 1)
                ->first();

            if ($doc) {
                $this->cache->set($cache_key, $doc, 3600 * 24); // 24 часа
            }
        }

        return $doc;
    }

    /**
     * Получить оферту
     */
    public function getOffer()
    {
        return $this->findByType('oferta');
    }

    /**
     * Получить политику конфиденциальности
     */
    public function getPrivacyPolicy()
    {
        return $this->findByType('personal data');
    }

    /**
     * Получить отказ от ответственности
     */
    public function getDisclaimer()
    {
        return $this->findByType('disclaimer');
    }

    /**
     * Получить пользовательское соглашение
     */
    public function getAgreement()
    {
        return $this->findByType('agreement');
    }
    
    /**
     * Получить версии документа
     */
    public function getVersions($type)
    {
        $qb = QueryBuilder();
        return $qb->table('doc')
            ->where('document_type', $type)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
    }

    /**
     * Очистить HTML документа
     */
    public function cleanHtml($doc)
    {
        if (!$doc)
            return '';

        // Убираем DOCTYPE, html, head, body
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $doc->document_text);
        $html = preg_replace('/<(html|head|body|meta|title)[^>]*>.*?<\/(html|head|body)>/is', '', $html);
        $html = str_replace(['<html>', '</html>', '<head>', '</head>', '<body>', '</body>'], '', $html);

        // Убираем лишние классы
        $html = preg_replace('/\sclass="[^"]*"/', '', $html);

        return $html;
    }

    /**
     * Создать новый документ
     */
    public function create($data)
    {
        if (empty($data['document_name']) || empty($data['document_type'])) {
            throw new \InvalidArgumentException('document_name и document_type обязательны');
        }

        // Если это текущая версия, сбрасываем флаг у других
        if (!empty($data['is_current'])) {
            $this->resetCurrentFlag($data['document_type']);
        }

        $defaults = [
            'document_version' => '1.0.0',
            'is_current' => 1,
            'created_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        $id = $this->connection->insert('doc', $data);

        if ($id) {
            EntityRelations::onEntityCreated($id, 'doc');

            $this->cache->delete(['doc', 'type', $data['document_type']]);

            do_action('bm_doc_created', $id, $data);
        }

        return $id;
    }

    /**
     * Обновить документ
     */
    public function update($id, $data)
    {
        unset($data['id']);

        $doc = $this->find($id);

        // Если делаем текущей версией, сбрасываем у других
        if (!empty($data['is_current']) && $doc && !$doc->is_current) {
            $this->resetCurrentFlag($doc->document_type);
        }
     
        $result =  $this->connection ->update('doc', $data, ['id' => $id]);

        if ($result) {
            EntityRelations::setEntityType($id, 'doc');

            $this->cache->delete(['doc', $id]);
            if ($doc) {
                $this->cache->delete(['doc', 'type', $doc->document_type]);
            }

            do_action('bm_doc_updated', $id, $data);
        }

        return $result;
    }

    /**
     * Удалить документ
     */
    public function delete($id)
    {
        $doc = $this->find($id);

        $result = $this->connection->delete('doc', ['id' => $id]);

        if ($result) {
            EntityRelations::removeAllRelations($id);

            $this->cache->delete(['doc', $id]);
            if ($doc) {
                $this->cache->delete(['doc', 'type', $doc->document_type]);
            }

            do_action('bm_doc_deleted', $id);
        }

        return $result;
    }

    /**
     * Получить все документы
     */
    public function getAll($limit = 100, $offset = 0)
    {
        $cache_key = ['docs', 'all', $limit, $offset];
        $docs = $this->cache->get($cache_key);

        if (!$docs) {
            $docs = $this->querybuilder($this->connection)->table('doc')
                ->orderBy('document_type')
                ->orderBy('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get();

            foreach ($docs as $doc) {
                $this->enrichDoc($doc);
            }

            $this->cache->set($cache_key, $docs, 600);
        }

        return $docs;
    }

    /**
     * Сбросить флаг is_current у всех документов данного типа
     */
    private function resetCurrentFlag($document_type)
    {
        $table = getTableName();

        return $this->connection->update(
            $table,
            ['is_current' => 0],
            ['document_type' => $document_type]
        );
    }

    /**
     * Создать новую версию документа
     */
    public function createVersion($type, $new_data)
    {
        // Сбрасываем текущую версию
        $this->resetCurrentFlag($type);

        // Увеличиваем версию
        $current = $this->findByType($type);
        if ($current) {
            $version_parts = explode('.', $current->document_version);
            $version_parts[2] = ((int) $version_parts[2]) + 1;
            $new_data['document_version'] = implode('.', $version_parts);
        }

        $new_data['document_type'] = $type;
        $new_data['is_current'] = 1;

        return $this->create($new_data);
    }

    function count(array $conditions = [])
    {
        //TODO
    }
function exists(array $conditions)
{
        //TODO  
}
function findAll($limit = 100, $offset = 0)
{
        //TODO
}
function findBy(array $conditions, $limit = null, $orderBy = null)
{
        //TODO
}
function findOneBy(array $conditions)
{
        //TODO
}
    function find($id)
    {
        //TODO
    }

}