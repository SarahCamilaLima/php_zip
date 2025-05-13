<?php

class ZipController {
    public function generate_zip() {
        $zip_dir = __DIR__ . "/zips";

        if (!file_exists($zip_dir)) {
            mkdir($zip_dir, 0777, true);
        }

        // Verifica se o array de arquivos foram enviados corretamente (image_list[])
        if (!isset($_FILES['image_list']) || !is_array($_FILES['image_list']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(["error" => "Nenhuma imagem enviada ou formato inválido."]);
            return;
        }

        // Reorganiza os dados em um array de arquivos
        $files = [];
        foreach ($_FILES['image_list']['tmp_name'] as $index => $tmp_name) {
            $files[] = [
                'tmp_name' => $tmp_name,
                'name' => $_FILES['image_list']['name'][$index],
                'size' => $_FILES['image_list']['size'][$index],
                'type' => $_FILES['image_list']['type'][$index],
                'error' => $_FILES['image_list']['error'][$index]
            ];
        }

        $valid_images = [];

        foreach ($files as $file) {
            if (!$file['tmp_name'] || !is_uploaded_file($file['tmp_name'])) continue;

            $check = getimagesize($file['tmp_name']);
            if ($check === false) continue;

            if ($file['size'] > 5242880) continue;

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ["jpg", "jpeg", "png", "gif", "webp"])) continue;

            $valid_images[] = $file;
        }

        if (empty($valid_images)) {
            http_response_code(409);
            echo json_encode(["error" => "Nenhuma imagem válida foi enviada."]);
            return;
        }

        $zip = new ZipArchive();
        $zipFileName = 'zip_image' . time() . '.zip';
        $zipFilePath = $zip_dir . '/' . $zipFileName;

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao criar o arquivo zip."]);
            return;
        }

        foreach ($valid_images as $img) {
            $content = file_get_contents($img['tmp_name']);
            if ($content !== false) {
                $zip->addFromString($img['name'], $content);
            }
        }

        $zip->close();

        if (file_exists($zipFilePath)) {
            http_response_code(201);
            echo json_encode([
                "message" => "Imagens processadas e zip gerado com sucesso.",
                "zip_path" => "./zips/" . $zipFileName
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Erro ao gerar o arquivo zip."]);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $zipUploader = new ZipController();
    $zipUploader->generate_zip();
}
