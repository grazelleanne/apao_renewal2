# APAO Renewal System Diagrams

These diagrams describe the current Laravel application based on its migrations, models, controllers, middleware, and routes.

The editable two-page Draw.io version is available at [`APAO_System_Diagrams.drawio`](./APAO_System_Diagrams.drawio). Open it in [diagrams.net](https://app.diagrams.net/) or the Draw.io desktop application.

The editable end-to-end operational flowchart is available at [`APAO_System_Flowchart.drawio`](./APAO_System_Flowchart.drawio). It covers authentication, staff registration and ICS submission, admin inspection and renewal, the repair/re-inspection loop, PAR issuance, persistence, notifications, audit logging, and reporting.

## Entity-Relationship Diagram

```mermaid
erDiagram
    USERS ||--o{ AUDIT_LOGS : creates
    USERS ||--o{ PERSONNEL_NOTIFICATIONS : receives
    USERS o|--o{ INSPECTIONS : performs
    USERS o|--o{ RENEWAL_TRANSACTIONS : processes
    USERS o|--o{ PROPERTY_ACKNOWLEDGEMENT_RECEIPTS : maintains
    PERSONNEL ||--o{ INSPECTIONS : undergoes
    PERSONNEL ||--o{ PROPERTY_ACKNOWLEDGEMENT_RECEIPTS : receives
    PERSONNEL ||--o{ RENEWAL_TRANSACTIONS : has
    PERSONNEL ||--o{ RENEWAL_HISTORY : has
    PERSONNEL o|--o{ NOTIFICATIONS : concerns
    PERSONNEL o|--o{ PROPERTY_ACKNOWLEDGEMENT_RECEIPTS : signs
    PROPERTY_ACKNOWLEDGEMENT_RECEIPTS o|--o{ PROPERTY_ACKNOWLEDGEMENT_RECEIPTS : replaces
    USERS o|--o| PASSWORD_RESETS_OTP : requests

    USERS {
        bigint id PK
        string email UK
        enum role
        boolean is_active
    }
    PERSONNEL {
        bigint id PK
        integer item_number UK
        string afp_serial_number UK
        string email UK
        string contact_number UK
        string pistol_serial_number UK
        enum approved_status
        string ics_status
    }
    INSPECTIONS {
        bigint id PK
        bigint personnel_id FK
        integer item_number
        enum status
        text remarks
        bigint inspected_by_user_id
    }
    PROPERTY_ACKNOWLEDGEMENT_RECEIPTS {
        bigint id PK
        string par_number UK
        bigint personnel_id FK
        bigint previous_par_id FK
        string status
        date issued_date
        date valid_until
        bigint created_by FK
        bigint updated_by FK
    }
    RENEWAL_TRANSACTIONS {
        bigint id PK
        bigint personnel_id FK
        integer item_number
        date renewal_date
        date new_validity_date
        enum status
        bigint processed_by_user_id
    }
    RENEWAL_HISTORY {
        bigint id PK
        integer item_number
        string action
        date date_of_validity
        date previous_validity
    }
    NOTIFICATIONS {
        bigint id PK
        string type
        integer personnel_id
        boolean read_by_admin
        boolean read_by_staff
    }
    PERSONNEL_NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        enum type
        boolean read
    }
    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string action
        string target
        string ip_address
        timestamp created_at
    }
    PASSWORD_RESETS_OTP {
        string email PK
        string otp
        timestamp expires_at
        timestamp created_at
    }
    ICS_SETTINGS {
        bigint id PK
        string office_name
        string chief_officer_name
        string issued_by_name
        string pistol_unit_cost
        string ammo_unit_cost
    }
```

The detailed ERD source, including important attributes, is in [`erd.mmd`](./erd.mmd).

### Relationship notes

- Database foreign keys connect personnel to inspections, renewal transactions, and PAR records.
- `renewal_history.item_number` and `notifications.personnel_id` are legacy logical references to `personnel.item_number`; they are not declared foreign keys.
- `inspections.inspected_by_user_id` and `renewal_transactions.processed_by_user_id` are logical user references without database-level foreign-key constraints.
- A PAR may reference a previous PAR, allowing replacement history to form a chain.
- `issued_by_personnel_id` and `approved_by_personnel_id` allow personnel records to act as PAR signatories.
- `ics_settings` is application-wide configuration and intentionally has no parent relationship.
- Laravel cache, session, and queue infrastructure tables are omitted because they are outside the APAO business domain.

## Use Case Diagram

```mermaid
flowchart LR
    Visitor[Unauthenticated User]
    Staff[Staff]
    Admin[Admin]
    SuperAdmin[Super Admin]
    Personnel[Personnel / Recipient]
    Mail[Email Service]

    subgraph System[APAO Renewal System]
        Login([Secure login with CAPTCHA])
        Reset([Password reset with OTP])
        Register([Register personnel and firearm])
        Validate([Check unique identifiers])
        Inspect([Inspect firearm])
        Renew([Process renewal])
        PAR([Manage PAR and replacements])
        ICS([Prepare ICS])
        Notify([Send notifications])
        Reports([Generate renewal and RPCSP reports])
        Users([Manage user accounts])
        Audit([Review audit trail])
        Archive([Manage personnel archive])
        Register -. includes .-> Validate
        Inspect --> Renew
        Renew --> Notify
    end

    Visitor --> Login
    Visitor --> Reset
    Staff --> Login
    Staff --> Register
    Staff --> PAR
    Staff --> ICS
    Staff --> Notify
    Staff --> Reports
    Admin --> Login
    Admin --> Inspect
    Admin --> Renew
    Admin --> Users
    Admin --> Audit
    Admin --> Archive
    Admin --> Reports
    SuperAdmin -. inherits .-> Admin
    Reset --> Mail
    Notify --> Mail
    Mail --> Personnel
```

The expanded actor-to-use-case source is in [`use-case.mmd`](./use-case.mmd).

## Actor summary

- **Super Admin:** Inherits Admin capabilities and has top-level administrative access.
- **Admin:** Manages accounts and personnel, conducts inspections, changes renewal status, manages archives, reviews audit logs, and generates reports.
- **Staff:** Registers personnel, validates identifiers, submits records for inspection, manages ICS/PAR documents, sends notifications, and produces reports.
- **Unauthenticated User:** Logs in through CAPTCHA protection or recovers an account through email OTP.
- **Personnel / Recipient:** Receives renewal, expiry, and status email notifications.
- **Email Service / Scheduler:** Supporting actors for OTP delivery and scheduled renewal notifications.
