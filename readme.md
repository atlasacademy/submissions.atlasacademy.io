# submissions.atlasacademy.io

submissions.atlasacademy.io is a API service intended for
connecting the submissions and drop rate sheets with other
outside services and devices.

### Available Routes

- GET /event
- GET /event/{uid}

### Works in Progress

__POST /submit/runs__

Request fields
```
event_uid => string
node_uid => string
runs => int
submitter => string | optional
drops[#][uid] => string
drops[#][amount] => int
```

__POST /submit/screenshot__

Request fields
```
event_uid => string
node_uid => string
submitter => string | optional
image => file | jpg or png
```

### Objects

__Event__
- uid
- sheet_id
- name
- sort
- submittable - Only events with this boolean will allow submissions. Otherwise it will return an error.

__Node__
- uid
- name
- submissions - Number of submitted runs
- submitters - Number of distinct submitters
- sort
