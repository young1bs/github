<?php

// 配置信息
$orgsAndTokens = array(
    array('org' => 'your org', 'token' => 'ghp_xxx'),
    // 更多组织和token...
);
$orgAndTokenIndex = 0;

class GitHubOrganizationManager
{
    private $token;
    private $headers;

    public function __construct($token)
    {
        $this->token = $token;
        $this->headers = array(
            'Authorization: Bearer ' . $this->token,
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: Mozilla/5.0 (compatible; MSIE 5.0; Windows 95; Trident/4.0)'
        );
    }

    public function inviteUserByEmail($organization, $email)
    {
        $url = 'https://api.github.com/orgs/' . $organization . '/invitations';
        $data = array('email' => $email, 'role' => 'direct_member');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $response = curl_exec($ch);
    
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        curl_close($ch);
    
        return $httpcode;

    }
}

// 获取email参数
$email = $_GET['email'] ?? null;
if (!$email) {
    http_response_code(400);
    echo json_encode(array('message' => 'Missing email'));
    exit();
}

$attempts = 0;
while ($attempts < count($orgsAndTokens)) {
    $orgAndToken = $orgsAndTokens[$orgAndTokenIndex];
    $manager = new GitHubOrganizationManager($orgAndToken['token']);
    $responseCode = $manager->inviteUserByEmail($orgAndToken['org'], $email);

    if ($responseCode == 201) {
        http_response_code(201);
        echo json_encode(array('message' => 'Invitation sent successfully.'));
        exit();
    } else {
        $orgAndTokenIndex = ($orgAndTokenIndex + 1) % count($orgsAndTokens);  // 切换到下一个组织和token
        $attempts++;
    }
}

http_response_code(429);
echo json_encode(array('message' => 'Invitation limit reached.'));
