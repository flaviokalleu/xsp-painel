// Correção para canais (get_live_streams)
if (isset($params['action']) && $params['action'] === "get_live_streams" && is_array($data)) {
    $fixed = [];
    $num = 1;

    foreach ($data as $canal) {
        $fixed[] = [
            "num"            => $num++,
            "name"           => $canal["name"] ?? "Sem Nome",
            "stream_type"    => $canal["stream_type"] ?? "live",
            "stream_id"      => $canal["stream_id"] ?? ($canal["id"] ?? null),
            "stream_icon"    => $canal["stream_icon"] ?? "",
            "epg_channel_id" => $canal["epg_channel_id"] ?? "",
            "added"          => $canal["added"] ?? time(),
            "category_id"    => $canal["category_id"] ?? "0",
            "custom_sid"     => $canal["custom_sid"] ?? "",
            "tv_archive"     => $canal["tv_archive"] ?? 0,
            "direct_source"  => $canal["direct_source"] ?? ($canal["link"] ?? ""),
        ];
    }

    $data = $fixed;
}
