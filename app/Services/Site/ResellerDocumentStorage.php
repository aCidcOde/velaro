<?php

/*
[Modulo: app/Services/Site]
@Author: André Gomes ( @acidcode )
@since 2026-09-05
Guarda os documentos do lojista em disco privado e registra cada arquivo em reseller_documents.
*/

namespace App\Services\Site;

use App\Models\Reseller;
use App\Models\ResellerDocument;
use Illuminate\Http\UploadedFile;

/**
 * O unico lugar que grava documento de revendedor.
 *
 * Os mesmos tres arquivos chegam por dois caminhos — o cadastro publico (tela
 * 1.4) e o reenvio de `awaiting_info` (regra 4 da tela 1.6) — e precisam cair no
 * mesmo disco, na mesma pasta e com a mesma linha em `reseller_documents`. Com a
 * gravacao em dois lugares, bastaria um deles mudar de disco para os documentos
 * de um revendedor ficarem em pastas diferentes e o Master perder metade.
 */
class ResellerDocumentStorage
{
    /**
     * Disco dos documentos: privado, nunca servido direto pela web.
     */
    public const DISK = 'local';

    /**
     * @param  array<string, UploadedFile>  $documents  tipo de `reseller_documents` => arquivo
     * @return list<ResellerDocument>
     */
    public function store(Reseller $reseller, array $documents): array
    {
        $stored = [];

        foreach ($documents as $type => $file) {
            $path = $file->store('reseller-documents/'.$reseller->protocol, self::DISK);

            $stored[] = ResellerDocument::create([
                'reseller_id' => $reseller->id,
                'type' => $type,
                'original_name' => $file->getClientOriginalName(),
                'disk' => self::DISK,
                'path' => is_string($path) ? $path : '',
                'size_bytes' => $file->getSize() ?: 0,
                'mime' => $file->getClientMimeType(),
            ]);
        }

        return $stored;
    }
}
