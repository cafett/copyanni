# copyanni
まだ完成していません。
[game_chef](https://github.com/suinua/game_chef) のテストで作成しているプラグインです  

## 使い方
### マップの設定
完全に[game_chef](https://github.com/suinua/game_chef) に依存しています  
詳しい仕様は[game_chefのドキュメント](https://github.com/suinua/game_chef/blob/master/doc/Map.md) を見てください  
  
`/map`  
1,TeamGameMapを作成  
2,チームを2個以上作成  
3,各チームごとにスポーンを設定  
4,各チームごとに、カスタム座標データ(key="nexus")を設定  
5,各チームごとに、カスタム座標データ(key="defender_position")を設定  

### Voteの設定
Configの`VoteMap`にVoteで使うワールド名を入れます  
使用されるマップはコピーされたものになり、試合終了後に削除されます  
`/vote`でvote一覧  
`/vote manage`でvoteの管理(作成や削除)


##　TODO
 - [ ] [職業](https://github.com/cafett/copyanni/issues/1)
 - [ ] ボス
 - [ ] ジャンプパッド
 - [ ] パーミッションエリア