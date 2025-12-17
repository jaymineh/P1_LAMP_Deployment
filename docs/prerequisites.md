# Prerequisites

This guide covers the prerequisites and initial setup required before deploying the LAMP stack.

## Table of Contents
- [System Requirements](#system-requirements)
- [AWS Account Setup](#aws-account-setup)
- [EC2 Instance Launch](#ec2-instance-launch)
- [Security Group Configuration](#security-group-configuration)
- [SSH Access Setup](#ssh-access-setup)
- [Domain Configuration (Optional)](#domain-configuration-optional)

## System Requirements

### Local Machine Requirements
- SSH client (OpenSSH, PuTTY, or similar)
- Terminal/Command Line access
- Text editor for editing files
- (Optional) Domain name for SSL setup

### Server Requirements
- **OS**: Ubuntu 22.04 LTS or Ubuntu 24.04 LTS
- **RAM**: Minimum 1GB (2GB+ recommended for production)
- **CPU**: 1 vCPU minimum (2+ recommended)
- **Storage**: Minimum 10GB (20GB+ recommended)
- **Network**: Public IP address

### Knowledge Prerequisites
- Basic Linux command line skills
- Understanding of SSH and file permissions
- Basic networking concepts
- Familiarity with text editors (vim, nano, etc.)

## AWS Account Setup

### 1. Create AWS Account
1. Go to [AWS Free Tier](https://aws.amazon.com/free/)
2. Click "Create a Free Account"
3. Follow the registration process
4. Verify your email and phone number
5. Add payment method (required even for free tier)

### 2. AWS Free Tier Limits
- **EC2**: 750 hours per month of t2.micro or t3.micro instances
- **Storage**: 30GB of EBS General Purpose (SSD) storage
- **Data Transfer**: 15GB of bandwidth out per month
- **Duration**: 12 months from signup date

> **Note**: Monitor your usage to avoid unexpected charges. Set up billing alerts in AWS Billing Console.

## EC2 Instance Launch

### Step 1: Navigate to EC2 Dashboard
1. Log into AWS Console
2. Select your preferred region (e.g., us-east-1)
3. Navigate to **Services** > **EC2** > **Instances**
4. Click **Launch Instance**

### Step 2: Configure Instance

#### Name and Tags
- **Name**: `lamp-stack-server` (or your preferred name)

#### Application and OS Images (Amazon Machine Image)
- **Quick Start**: Ubuntu
- **AMI**: Ubuntu Server 24.04 LTS (HVM), SSD Volume Type
- **Architecture**: 64-bit (x86)

#### Instance Type
- **Type**: t2.micro or t3.micro (free tier eligible)
- **Details**: 1 vCPU, 1GB RAM

#### Key Pair (Login)
- Click **Create new key pair**
- **Name**: `lamp-stack-key`
- **Type**: RSA
- **Format**: 
  - `.pem` for Linux/Mac
  - `.ppk` for Windows (PuTTY)
- **Download** and save securely
- **Important**: Set proper permissions:
  ```bash
  chmod 400 lamp-stack-key.pem
  ```

#### Network Settings
- **VPC**: Default (or create a custom VPC)
- **Subnet**: No preference (default)
- **Auto-assign Public IP**: Enable

#### Configure Storage
- **Root Volume**: 20 GB gp3 (SSD)
- **Delete on Termination**: Enabled (default)

### Step 3: Launch Instance
1. Review configuration
2. Click **Launch Instance**
3. Wait for instance state to show "Running"
4. Note the **Public IPv4 address**

## Security Group Configuration

Security groups act as a virtual firewall for your EC2 instance.

### Default Inbound Rules

Create or edit the security group with the following rules:

| Type  | Protocol | Port Range | Source      | Description                    |
|-------|----------|------------|-------------|--------------------------------|
| SSH   | TCP      | 22         | My IP       | SSH access from your location  |
| HTTP  | TCP      | 80         | 0.0.0.0/0   | Web traffic (HTTP)             |
| HTTP  | TCP      | 80         | ::/0        | Web traffic (HTTP IPv6)        |
| HTTPS | TCP      | 443        | 0.0.0.0/0   | Secure web traffic (HTTPS)     |
| HTTPS | TCP      | 443        | ::/0        | Secure web traffic (HTTPS IPv6)|

### Security Best Practices

1. **SSH Access**:
   - Restrict SSH (port 22) to your IP address or known IP ranges
   - Use "My IP" in the Source field for SSH rule
   - Update if your IP changes

2. **Web Traffic**:
   - Allow HTTP (80) and HTTPS (443) from anywhere (0.0.0.0/0)
   - This is necessary for public website access

3. **Additional Security** (covered in main tutorial):
   - We'll configure UFW firewall on the server
   - Implement fail2ban for brute-force protection
   - Set up SSH key authentication only

### To Edit Security Group

1. Go to **EC2** > **Security Groups**
2. Select your instance's security group
3. Click **Edit inbound rules**
4. Add/modify rules as shown above
5. Click **Save rules**

## SSH Access Setup

### For Linux/Mac Users

1. **Set key permissions**:
   ```bash
   chmod 400 ~/Downloads/lamp-stack-key.pem
   ```

2. **Connect to instance**:
   ```bash
   ssh -i ~/Downloads/lamp-stack-key.pem ubuntu@YOUR_PUBLIC_IP
   ```

3. **Create SSH config** (optional, for easier access):
   ```bash
   # Edit ~/.ssh/config
   Host lamp-stack
       HostName YOUR_PUBLIC_IP
       User ubuntu
       IdentityFile ~/Downloads/lamp-stack-key.pem
       ServerAliveInterval 60
   ```
   
   Then connect with:
   ```bash
   ssh lamp-stack
   ```

### For Windows Users

#### Option 1: Using OpenSSH (Windows 10/11)
```powershell
ssh -i C:\path\to\lamp-stack-key.pem ubuntu@YOUR_PUBLIC_IP
```

#### Option 2: Using PuTTY
1. Download and install [PuTTY](https://www.putty.org/)
2. Convert .pem to .ppk using PuTTYgen (or download .ppk during key creation)
3. Open PuTTY:
   - **Host Name**: ubuntu@YOUR_PUBLIC_IP
   - **Port**: 22
   - **Connection** > **SSH** > **Auth**: Browse and select .ppk file
   - **Session**: Save for future use
4. Click **Open** to connect

### First Connection

On first connection, you'll see a warning about host authenticity. Type `yes` to continue.

```bash
The authenticity of host 'xx.xx.xx.xx' can't be established.
ECDSA key fingerprint is SHA256:...
Are you sure you want to continue connecting (yes/no/[fingerprint])? yes
```

### Verify Connection

Once connected, you should see:
```bash
Welcome to Ubuntu 24.04 LTS (GNU/Linux ...)
ubuntu@ip-xxx-xx-xx-xx:~$
```

## Domain Configuration (Optional)

If you plan to use SSL/TLS with Let's Encrypt, you'll need a domain name.

### Register a Domain

Popular registrars:
- Namecheap
- GoDaddy
- Google Domains
- AWS Route 53

### Configure DNS Records

Add an **A Record** pointing to your EC2 instance's public IP:

| Type | Name             | Value           | TTL  |
|------|------------------|-----------------|------|
| A    | @                | YOUR_PUBLIC_IP  | 3600 |
| A    | www              | YOUR_PUBLIC_IP  | 3600 |

### Verify DNS Propagation

Wait 5-30 minutes for DNS propagation, then verify:

```bash
# Linux/Mac
nslookup yourdomain.com
dig yourdomain.com

# Windows
nslookup yourdomain.com
```

The IP address returned should match your EC2 instance's public IP.

### Elastic IP (Recommended for Production)

EC2 instances get a new public IP when stopped/started. For production:

1. Allocate an **Elastic IP** in EC2 Console
2. Associate it with your instance
3. Update DNS records with the Elastic IP
4. Remember: Elastic IPs are free when associated with a running instance

## Next Steps

Once you've completed all prerequisites:

1. ✅ AWS account created
2. ✅ EC2 instance running
3. ✅ Security groups configured
4. ✅ SSH access working
5. ✅ (Optional) Domain configured

You're ready to proceed with the [main deployment guide](../project.md)!

## Troubleshooting

### Can't connect via SSH

1. **Check security group**: Ensure port 22 is open for your IP
2. **Verify key permissions**: Should be 400 (chmod 400 keyfile.pem)
3. **Check instance state**: Must be "Running"
4. **Correct username**: Use `ubuntu` for Ubuntu AMIs
5. **Correct IP**: Use the Public IPv4 address, not private

### Connection timeout

1. **Security group**: Verify SSH rule allows your current IP
2. **VPC/Subnet**: Ensure instance is in a public subnet
3. **Internet Gateway**: VPC must have an internet gateway attached
4. **Route table**: Check routes allow internet access

### Permission denied (publickey)

1. **Wrong key file**: Verify you're using the correct .pem file
2. **Key permissions**: Must be readable only by you (chmod 400)
3. **Wrong username**: Use `ubuntu` not `root` or `ec2-user`

For more troubleshooting tips, see [docs/troubleshooting.md](./troubleshooting.md).
